<?php

namespace Orion\Tests\Fixtures\App\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Orion\Contracts\ComponentsResolver;
use Orion\Contracts\Paginator;
use Orion\Contracts\ParamsValidator;
use Orion\Contracts\QueryBuilder;
use Orion\Contracts\RelationsResolver;
use Orion\Contracts\SearchEngine;
use Orion\Http\Middleware\EnforceExpectsJson;
use Orion\Orion;
use Orion\Specs\ResourcesCacheStore;

class OrionServiceProvider extends ServiceProvider
{

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('orion', Orion::class);
        $this->app->bind(QueryBuilder::class, \Orion\Drivers\Standard\QueryBuilder::class);
        $this->app->bind(RelationsResolver::class, \Orion\Drivers\Standard\RelationsResolver::class);
        $this->app->bind(ParamsValidator::class, \Orion\Drivers\Standard\ParamsValidator::class);
        $this->app->bind(Paginator::class, \Orion\Drivers\Standard\Paginator::class);
        $this->app->bind(SearchEngine::class, \Orion\Drivers\Standard\SearchEngines\DatabaseSearchEngine::class);
        $this->app->bind(ComponentsResolver::class, \Orion\Drivers\Standard\ComponentsResolver::class);

        $this->app->singleton(ResourcesCacheStore::class);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        app()->make(Kernel::class)->pushMiddleware(EnforceExpectsJson::class);

        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
