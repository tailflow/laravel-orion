<?php

namespace Orion;

use Illuminate\Support\ServiceProvider;
use Orion\Http\Middleware\EnforceExpectsJson;

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
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        app('router')->pushMiddlewareToGroup('api', EnforceExpectsJson::class);
    }
}
