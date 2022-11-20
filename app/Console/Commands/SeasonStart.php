<?php

namespace App\Console\Commands;

use App\Models\Gamification\Season;
use Illuminate\Console\Command;

class SeasonStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'season:start {seasonId}';

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
        $seasonId = $this->argument('seasonId');
        if (env('APP_SEASON') !== $seasonId) {
            $this->error('Vai tomar no cu troca a porra do negocio no env');
            return false;
        }

        Season::find($seasonId)->seasonStatus(true);

        $this->info('Beleza tá tudo certo pode deixa a galera se matar por pontinho');
        $this->info('Te vejo ano que vem, é nois <3');
    }
}
