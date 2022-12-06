<?php

namespace App\Console;

use App\Console\Commands\MigrateUsers;
use App\Console\Commands\SeasonStart;
use App\Console\Commands\FinishSeasonCommand;
use App\Console\Commands\SendLevelupMessage;
use Illuminate\Console\Scheduling\Schedule;
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
        SeasonStart::class,
        MigrateUsers::class,
        SendLevelupMessage::class
    ];

    public function __construct(Application $app)
    {
        parent::__construct($app);
        if (class_exists(\Knuckles\Scribe\Commands\GenerateDocumentation::class)) {
            $this->commands[] = \Knuckles\Scribe\Commands\GenerateDocumentation::class;
        }
        if (class_exists(\Knuckles\Scribe\Commands\MakeStrategy::class)) {
            $this->commands[] = \Knuckles\Scribe\Commands\MakeStrategy::class;
        }
        if (class_exists(\Knuckles\Scribe\Commands\Upgrade::class)) {
            $this->commands[] = \Knuckles\Scribe\Commands\Upgrade::class;
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
