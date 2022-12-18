<?php

namespace App\Console\Commands;

use App\Clients\DiscordClient;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Intents;
use Illuminate\Console\Command;

class SendLevelupMessage extends Command
{
    protected $signature = 'discord:level-up {discordId} {level} {messagesCount}';
    protected $description = 'Wipe database and start the next season';
    protected array $member = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(DiscordClient $discordClient): int
    {
        return self::SUCCESS;
        $discord = new Discord([
            'token' => config('he4rt.discord.token'),
            'intents' => Intents::getDefaultIntents()
        ]);

        $member = $discordClient->getUser(
            $this->argument('discordId')
        );

        $discord->on('ready', function (Discord $discord) use ($member) {
            $message = $this->setupMessage($discord, $member);
            $discord->getChannel(config('he4rt.discord.levelup_channel_id'))
                ->sendMessage($message)
                ->done(fn() => $discord->close());
        });

        $discord->run();
        return self::SUCCESS;
    }

    private function setupMessage(Discord $discord, $member): MessageBuilder
    {
        $embed = new Embed($discord);
        $embed
            ->setTitle(sprintf('🆙 %s subiu para o nível %s!', $member['username'], $this->argument('level')))
            ->setDescription(sprintf('Mensagens nessa Temporada: %s', $this->argument('messagesCount')))
            ->setThumbnail(sprintf(
                'https://cdn.discordapp.com/avatars/%s/%s.png?size=256',
                $member['id'],
                $member['avatar']
            ))
            ->setColor('#8146dc')
            ->setFooter(sprintf('%s © He4rt Developers • Hoje às %s', date('Y'), date('H:i')));

        return MessageBuilder::new()
            ->setContent(sprintf('<@%s>', $member['id']))
            ->addEmbed($embed);
    }

    private function dispatchMessage(Discord $discord, string $discordId): void
    {
        $discord->getHttpClient()
            ->get(str_replace(':user_id', $discordId, Endpoint::USER));
    }
}
