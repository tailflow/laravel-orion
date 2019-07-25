<?php

namespace Orion;


use Illuminate\Support\ServiceProvider;
use Orion\Commands\BuildSpecsCommand;
use Orion\Concerns\EloquentBuilder;
use Orion\Concerns\HandlesEloquentOperations;
use Orion\Contracts\ComponentsResolver;
use Orion\Contracts\EloquentOperations;
use Orion\Contracts\Paginator;
use Orion\Contracts\ParamsValidator;
use Orion\Contracts\QueryBuilder;
use Orion\Contracts\RelationsResolver;
use Orion\Contracts\SearchBuilder;
use Orion\Drivers\Standard\OrionBuilder;
use Orion\Http\Middleware\EnforceExpectsJson;
use Orion\Jobs\JobDispatcher;
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

        $this->app->singleton('Tracer',function ($app){
            return new class  {

                private $tracerFun;
                public function register($tracer) {
                    $this->tracerFun = $tracer;
                }

                public function trace($args){
                    call_user_func($this->tracerFun, $args);
                }
            };
        });

        $this->app->bind('QueryBuilder',function ($app){
            return new class implements EloquentOperations {
                use HandlesEloquentOperations, EloquentBuilder;

                private  $instance;

                public function __construct(
                    protected ?string $model = null,
                ){
                    $this->instance = $this;
                }
            };
        });

        $this->app->singleton('JobResolver', function () {
            return new JobDispatcher();
        });

        $this->app->singleton('OrionBuilder', function () {
            return app(OrionBuilder::class);
        });
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
