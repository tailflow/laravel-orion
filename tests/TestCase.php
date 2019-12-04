<?php

namespace Orion\Tests;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
        $this->withFactories(__DIR__.'/Fixtures/database/factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            'Orion\Tests\Fixtures\App\Providers\OrionServiceProvider'
        ];
    }
}
