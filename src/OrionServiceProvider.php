<?php

namespace Orion;

use Illuminate\Support\ServiceProvider;
use Orion\Commands\BuildSpecsCommand;
use Orion\Contracts\ComponentsResolver;
use Orion\Contracts\Paginator;
use Orion\Contracts\ParamsValidator;
use Orion\Contracts\QueryBuilder;
use Orion\Contracts\RelationsResolver;
use Orion\Contracts\SearchBuilder;
use Orion\Http\Middleware\EnforceExpectsJson;
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
        $this->app->bind(QueryBuilder::class, Drivers\Standard\QueryBuilder::class);
        $this->app->bind(RelationsResolver::class, Drivers\Standard\RelationsResolver::class);
        $this->app->bind(ParamsValidator::class, Drivers\Standard\ParamsValidator::class);
        $this->app->bind(Paginator::class, Drivers\Standard\Paginator::class);
        $this->app->bind(SearchBuilder::class, Drivers\Standard\SearchBuilder::class);
        $this->app->bind(ComponentsResolver::class, Drivers\Standard\ComponentsResolver::class);

        $this->app->singleton(ResourcesCacheStore::class);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        app('router')->pushMiddlewareToGroup('api', EnforceExpectsJson::class);


        $this->publishes(
            [
                __DIR__ . '/../config/orion.php' => config_path('orion.php'),
            ],
            'orion-config'
        );

        $this->mergeConfigFrom(__DIR__ . '/../config/orion.php', 'orion');

        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    BuildSpecsCommand::class,
                ]
            );
        }
    }
}
