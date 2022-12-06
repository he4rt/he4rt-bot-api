<?php

namespace Tests\Feature\Commands;

use App\Models\Gamefication\Season;
use Carbon\Carbon;
use Laravel\Lumen\Testing\DatabaseMigrations;
use TestCase;

class FinishSeasonCommandTest extends TestCase
{
    use DatabaseMigrations;

    public function test_command()
    {
        $this->artisan('db:seed');

        $this->artisan('season:end');
    }


}
