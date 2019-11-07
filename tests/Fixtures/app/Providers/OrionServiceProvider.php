<?php

namespace Orion\Tests\Fixtures\App\Providers;

use Illuminate\Support\ServiceProvider;

class OrionServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
