<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeasonWipe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wipe:season';

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

        DB::transaction(function () {
            DB::table('users')
                ->orderByDesc('level')
                ->chunk(300, function ($users) {
                    foreach ($users as $user) {
                        $this->info('Nickname: ' . $user->nickname);
                    }
                });
        });
    }
}
