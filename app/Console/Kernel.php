<?php

namespace App\Console;

use App\Console\Commands\MigrateUsers;
use App\Console\Commands\SeasonStart;
use App\Console\Commands\SeasonWipe;
use App\Console\Commands\SendLevelupMessage;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SeasonWipe::class,
        SeasonStart::class,
        MigrateUsers::class,
        SendLevelupMessage::class
    ];

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
