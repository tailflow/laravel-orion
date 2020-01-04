<?php

namespace Orion;

use Illuminate\Support\ServiceProvider;
use Orion\Http\Middleware\EnforceExpectsJson;

class OrionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        app('router')->pushMiddlewareToGroup('api', EnforceExpectsJson::class);
    }
}
