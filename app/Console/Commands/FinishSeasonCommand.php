<?php

namespace App\Console\Commands;

use App\Actions\Gamefication\Season\FinishSeason;
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

    public function handle(FinishSeason $action): int
    {
        $currentSeason = Season::query()->currentSeason()->first();

        $this->info(sprintf(
            'Iniciando finalização da %sa temporada -> %s <- ',
            $currentSeason->id,
            $currentSeason->name
        ));
        $this->info('Você tem certeza que deseja finalizar essa temporada?');
        $this->info('Finalização de temporada iniciada!');

        $action->handle();

        $this->info('Finalização de temporada finalizada!');
        $this->info('Lembre-se de trocar o APP_SEASON no env para o valor da nova temporada.');
        $this->info('Deus tenha piedade do server, amém');

        return self::SUCCESS;
    }
}
