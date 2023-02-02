<?php

namespace App\Console\Commands;

use App\Actions\Commands\MigrateDatabase as MigrateDatabaseAction;
use Illuminate\Console\Command;

class MigrateDatabase extends Command
{
    protected $signature = 'system:v2';

    protected $description = 'Migrate all the database from v1 to v2';

    public function handle(MigrateDatabaseAction $action): int
    {
        $action->run();

        return self::SUCCESS;
    }
}
