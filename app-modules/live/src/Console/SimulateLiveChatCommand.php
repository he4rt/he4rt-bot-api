<?php

declare(strict_types=1);

namespace He4rt\Live\Console;

use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Chat\Dev\FakeChatAuthors;
use He4rt\Live\Models\Live;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

/**
 * Envia mensagens de chat fake para uma live, para teste visual do chat (ambiente dev).
 * Roda até a flag de cache ser desligada (pela action do painel) ou até `--limit` ser atingido.
 */
final class SimulateLiveChatCommand extends Command
{
    protected $signature = 'live:simulate-chat {live} {--limit=} {--interval-min=2} {--interval-max=6}';

    protected $description = 'Simula comentários de chat numa live, para teste visual (ambiente dev)';

    public static function cacheKey(Live $live): string
    {
        return "live:chat-simulation:{$live->id}";
    }

    /** A flag precisa ser vista pelo processo em background e por requisições HTTP futuras: 'file' persiste entre processos, ao contrário do store padrão ('array' em dev). */
    public static function cacheStore(): Repository
    {
        return Cache::store('file');
    }

    public function handle(SendChatMessage $sendChatMessage, FakeChatAuthors $authors): int
    {
        $live = Live::query()->findOrFail($this->argument('live'));
        $cacheKey = self::cacheKey($live);
        $store = self::cacheStore();
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $intervalMin = (int) $this->option('interval-min');
        $intervalMax = (int) $this->option('interval-max');

        $store->add($cacheKey, value: true);

        $sent = 0;

        while ($store->get($cacheKey) === true) {
            $sendChatMessage->execute($authors->random(), $live, $authors->randomPhrase());
            $sent++;

            if ($limit !== null && $sent >= $limit) {
                break;
            }

            Sleep::sleep(random_int($intervalMin, $intervalMax));
        }

        return self::SUCCESS;
    }
}
