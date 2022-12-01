<?php

namespace App\Console\Commands;

use App\Models\Gamefication\Season;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeasonWipe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'season:wipe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe database and start the next season';

    public function handle(): int
    {
        $season = Season::query()->currentSeason()
            ->first();
        dd($season);


        $this->info(
            sprintf('Iniciando finalização da %s temporada -> %s <- ', )
        );
        $this->info('Você tem certeza que deseja iniciar a nova temporada?');


        $this->confirm('Essa ação não pode ser desfeita');
        $activeSeason = Season::find(env('APP_SEASON'));

        $this->info('Finalização de temporada iniciada!');
        DB::transaction(function () use ($activeSeason) {
            User::orderBy('id')
                ->chunk(300, function ($users) use ($activeSeason) {
                    foreach ($users as $user) {
                        $messagesCount = $user->messages()
                            ->whereBetween('created_at', [
                                $activeSeason->start, $activeSeason->end
                            ])->count();
                        $user->seasonInfo()->create([
                            'season_id' => env('APP_SEASON'),
                            'level' => $user->level,
                            'messages_count' => $messagesCount
                        ]);
                        $user->wipe();
                    }
                });
        });

        $activeSeason->seasonStatus(false);

        $this->info('Finalização de temporada finalizada!');
        $this->info('Lembre-se de trocar o APP_SEASON no env para o valor da nova temporada.');
        $this->info('Deus tenha piedade do server, amém');
    }
}
