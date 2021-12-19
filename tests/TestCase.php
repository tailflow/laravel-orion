<?php

namespace Orion\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/Fixtures/database/factories');

        $this->withoutExceptionHandling(
            [
                AuthorizationException::class,
                NotFoundHttpException::class,
                ModelNotFoundException::class,
                ValidationException::class,
            ]
        );
    }

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate', ['--path' => __DIR__ . '/Fixtures/database/migrations', '--realpath' => true]);

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
            $this->artisan('migrate', ['--path' => __DIR__ . '/Fixtures/database/migrations', '--realpath' => true]);

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    protected function getPackageProviders($app)
    {
        return [
            'Orion\Tests\Fixtures\App\Providers\OrionServiceProvider',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.guards.api', [
            'driver' => 'token',
            'provider' => 'users',
        ]);
    }
}
