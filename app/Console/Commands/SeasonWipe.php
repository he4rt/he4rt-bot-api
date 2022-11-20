<?php

namespace App\Console\Commands;

use App\Models\Gamification\Season;
use App\Models\User\User;
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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Iniciando finalização da ' . env('APP_SEASON') . ' temporada');
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
