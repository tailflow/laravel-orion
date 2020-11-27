<?php

namespace Orion\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/Fixtures/database/factories');
    }

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate', ['--path' => __DIR__.'/Fixtures/database/migrations', '--realpath' => true]);

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Refresh a conventional test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (!RefreshDatabaseState::$migrated) {
            $this->artisan('migrate', ['--path' => __DIR__.'/Fixtures/database/migrations', '--realpath' => true]);

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    protected function getPackageProviders($app)
    {
        return [
            'Orion\Tests\Fixtures\App\Providers\OrionServiceProvider'
        ];
    }
}
