<?php

namespace Orion\Tests\Fixtures\App\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Orion\Http\Middleware\EnforceExpectsJson;
use Orion\Orion;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Policies\PostPolicy;

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
        app()->make(Kernel::class)->pushMiddleware(EnforceExpectsJson::class);

        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        Gate::policy(Post::class, PostPolicy::class);
    }
}
