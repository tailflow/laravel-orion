<?php

namespace Orion\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;
use Orion\Tests\Fixtures\App\Http\Controllers\DummyController;

class OrionTest extends TestCase
{
    /** @test */
    public function registering_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::resource('dummy', DummyController::class);
        });

        $this->assertRouteRegistered('api.dummy.search', ['POST'], 'api/dummy/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.dummy.index', ['GET', 'HEAD'], 'api/dummy', DummyController::class.'@index');
        $this->assertRouteRegistered('api.dummy.store', ['POST'], 'api/dummy', DummyController::class.'@store');
        $this->assertRouteRegistered('api.dummy.show', ['GET', 'HEAD'], 'api/dummy/{dummy}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.dummy.update', ['PUT', 'PATCH'], 'api/dummy/{dummy}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.dummy.destroy', ['DELETE'], 'api/dummy/{dummy}', DummyController::class.'@destroy');

        $this->assertRouteNotRegistered('api.dummy.restore');
    }

    /** @test */
    public function registering_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::resource('dummy', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.dummy.search', ['POST'], 'api/dummy/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.dummy.index', ['GET', 'HEAD'], 'api/dummy', DummyController::class.'@index');
        $this->assertRouteRegistered('api.dummy.store', ['POST'], 'api/dummy', DummyController::class.'@store');
        $this->assertRouteRegistered('api.dummy.show', ['GET', 'HEAD'], 'api/dummy/{dummy}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.dummy.update', ['PUT', 'PATCH'], 'api/dummy/{dummy}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.dummy.destroy', ['DELETE'], 'api/dummy/{dummy}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.dummy.restore', ['POST'], 'api/dummy/{dummy}/restore', DummyController::class.'@restore');
    }

    /**
     * Asserts that a route with the given signature is registered.
     *
     * @param string $name
     * @param array $methods
     * @param string $uri
     * @param string $controller
     */
    protected function assertRouteRegistered(string $name, array $methods, string $uri, string $controller)
    {
        $routes = Route::getRoutes();
        /**
         * @var \Illuminate\Routing\Route $route
         */
        $route = $routes->getByName($name);

        $this->assertNotNull($route);
        $this->assertSame($methods, $route->methods);
        $this->assertSame($uri, $route->uri);
        $this->assertSame($controller, $route->action['controller']);
    }

    /**
     * Assert that a route with the given name is not registered.
     *
     * @param string $name
     */
    protected function assertRouteNotRegistered(string $name)
    {
        $this->assertNull(Route::getRoutes()->getByName($name));
    }
}