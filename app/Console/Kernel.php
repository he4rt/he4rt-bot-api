<?php

namespace App\Console;

use App\Console\Commands\FinishSeasonCommand;
use App\Console\Commands\MigrateDatabase;
use App\Console\Commands\RecordRankingCommand;
use App\Console\Commands\SendLevelupMessage;
use App\Console\Commands\UpdateAvatarCommand;
use Illuminate\Console\Scheduling\Schedule;
use Knuckles\Scribe\Commands\GenerateDocumentation;
use Knuckles\Scribe\Commands\MakeStrategy;
use Knuckles\Scribe\Commands\Upgrade;
use Laravel\Lumen\Application;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        FinishSeasonCommand::class,
        SendLevelupMessage::class,
        UpdateAvatarCommand::class,
        RecordRankingCommand::class,
        MigrateDatabase::class,
    ];

    public function __construct(Application $app)
    {
        parent::__construct($app);
        if (class_exists(GenerateDocumentation::class)) {
            $this->commands[] = GenerateDocumentation::class;
        }
        if (class_exists(MakeStrategy::class)) {
            $this->commands[] = MakeStrategy::class;
        }
        if (class_exists(Upgrade::class)) {
            $this->commands[] = Upgrade::class;
        }
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
