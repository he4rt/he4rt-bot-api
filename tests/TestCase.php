<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function actingAsAdmin(): self
    {
        return $this->withHeaders([
            'Authorization' => config('he4rt.server_key')
        ]);
    }
}
