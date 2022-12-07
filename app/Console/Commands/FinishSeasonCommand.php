<?php

namespace App\Console\Commands;

use App\Models\Gamefication\Season;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FinishSeasonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'season:end';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe database and start the next season';

    public function handle(): int
    {
        $currentSeason = Season::query()->currentSeason()->first();

        $this->info(sprintf('Iniciando finalização da %sa temporada -> %s <- ',
            $currentSeason->id,
            $currentSeason->name
        ));
        $this->info('Você tem certeza que deseja finalizar essa temporada?');
        $this->confirm('Essa ação não pode ser desfeita');

        $this->info('Finalização de temporada iniciada!');
        DB::transaction(function () use ($currentSeason) {
            User::query()
                ->where('level', '>', 3)
                ->orderBy('id')->chunk(
                    500,
                    fn(Collection $users) => $this->handleUsers($users, $currentSeason)
                );
        });
        $this->info('Finalização de temporada finalizada!');
        $this->info('Lembre-se de trocar o APP_SEASON no env para o valor da nova temporada.');
        $this->info('Deus tenha piedade do server, amém');

        return self::SUCCESS;
    }

    private function handleUsers(Collection $users, Season $currentSeason)
    {
        /** @var User $user */
        foreach ($users as $user) {
            $messagesCount = $user->messages()
                ->whereBetween('created_at', [$currentSeason->starts_at, $currentSeason->ends_at])->count();

            $user->seasonInfo()->create([
                'season_id' => $currentSeason->getKey(),
                'level' => $user->level,
                'messages_count' => $messagesCount
            ]);
            $user->wipe();
        }
    }
}
