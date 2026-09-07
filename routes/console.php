<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune', ['--hours' => 36, '--keep-exceptions'])
    ->when(fn () => config('telescope.enabled'))
    ->daily();

Schedule::command('cloudflare:reload')
    ->when(fn () => config('laravelcloudflare.enabled'))
    ->twiceMonthly(3, 17);

Schedule::command('backup:run', ['--only-db'])
    ->when(fn () => config('backup.enabled'))
    ->everyFifteenMinutes();

Schedule::command('backup:clean')
    ->when(fn () => config('backup.enabled'))
    ->everyOddHour();

Schedule::command('backup:monitor')
    ->when(fn () => config('backup.enabled'))
    ->daily()
    ->at('09:00')
    ->timezone(config('app.display_timezone'));

Schedule::command('live:sample-viewers')->everyMinute();
