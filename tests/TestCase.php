<?php

use Drfraker\SnipeMigrations\SnipeMigrations;
use Laravel\Lumen\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => config('he4rt.server_key')
        ];
    }
}
