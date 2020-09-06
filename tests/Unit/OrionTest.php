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
            Orion::resource('projects', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.search', ['POST'], 'api/projects/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.index', ['GET', 'HEAD'], 'api/projects', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.store', ['POST'], 'api/projects', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.show', ['GET', 'HEAD'], 'api/projects/{project}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.update', ['PUT', 'PATCH'], 'api/projects/{project}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.destroy', ['DELETE'], 'api/projects/{project}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.batchStore', ['POST'], 'api/projects/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.batchUpdate', ['PATCH'], 'api/projects/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.batchDestroy', ['DELETE'], 'api/projects/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.restore');
        $this->assertRouteNotRegistered('api.projects.batchRestore');
    }

    /** @test */
    public function registering_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::resource('projects', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.search', ['POST'], 'api/projects/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.index', ['GET', 'HEAD'], 'api/projects', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.store', ['POST'], 'api/projects', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.show', ['GET', 'HEAD'], 'api/projects/{project}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.update', ['PUT', 'PATCH'], 'api/projects/{project}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.destroy', ['DELETE'], 'api/projects/{project}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.restore', ['POST'], 'api/projects/{project}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.batchStore', ['POST'], 'api/projects/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.batchUpdate', ['PATCH'], 'api/projects/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.batchDestroy', ['DELETE'], 'api/projects/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.batchRestore', ['POST'], 'api/projects/batch/restore', DummyController::class.'@batchRestore');
    }

    /** @test */
    public function registering_has_one_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasOneResource('projects', 'meta', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.meta.store', ['POST'], 'api/projects/{project}/meta', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.meta.show', ['GET', 'HEAD'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.meta.update', ['PUT', 'PATCH'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.meta.destroy', ['DELETE'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.meta.batchStore', ['POST'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.meta.batchUpdate', ['PATCH'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.meta.batchDestroy', ['DELETE'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.meta.index');
        $this->assertRouteNotRegistered('api.projects.meta.search');
        $this->assertRouteNotRegistered('api.projects.meta.restore');
        $this->assertRouteNotRegistered('api.projects.meta.batchRestore');
    }

    /** @test */
    public function registering_has_one_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasOneResource('projects', 'meta', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.meta.store', ['POST'], 'api/projects/{project}/meta', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.meta.show', ['GET', 'HEAD'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.meta.update', ['PUT', 'PATCH'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.meta.destroy', ['DELETE'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.meta.restore', ['POST'], 'api/projects/{project}/meta/{metum?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.meta.batchStore', ['POST'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.meta.batchUpdate', ['PATCH'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.meta.batchDestroy', ['DELETE'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.meta.batchRestore', ['POST'], 'api/projects/{project}/meta/batch/restore', DummyController::class.'@batchRestore');

        $this->assertRouteNotRegistered('api.projects.meta.index');
        $this->assertRouteNotRegistered('api.projects.meta.search');
    }

    /** @test */
    public function registering_belongs_to_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::belongsToResource('projects', 'owner', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.owner.show', ['GET', 'HEAD'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.owner.update', ['PUT', 'PATCH'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.owner.destroy', ['DELETE'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.owner.batchUpdate', ['PATCH'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.owner.batchDestroy', ['DELETE'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.owner.store');
        $this->assertRouteNotRegistered('api.projects.owner.batchStore');
        $this->assertRouteNotRegistered('api.projects.owner.index');
        $this->assertRouteNotRegistered('api.projects.owner.search');
        $this->assertRouteNotRegistered('api.projects.owner.restore');
        $this->assertRouteNotRegistered('api.projects.owner.batchRestore');
    }

    /** @test */
    public function registering_belongs_to_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::belongsToResource('projects', 'owner', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.owner.show', ['GET', 'HEAD'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.owner.update', ['PUT', 'PATCH'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.owner.destroy', ['DELETE'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.owner.restore', ['POST'], 'api/projects/{project}/owner/{owner?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.owner.batchUpdate', ['PATCH'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.owner.batchDestroy', ['DELETE'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.owner.batchRestore', ['POST'], 'api/projects/{project}/owner/batch/restore', DummyController::class.'@batchRestore');

        $this->assertRouteNotRegistered('api.projects.owner.store');
        $this->assertRouteNotRegistered('api.projects.owner.batchStore');
        $this->assertRouteNotRegistered('api.projects.owner.index');
        $this->assertRouteNotRegistered('api.projects.owner.search');
    }

    /** @test */
    public function registering_has_many_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasManyResource('projects', 'templates', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.templates.index', ['GET', 'HEAD'], 'api/projects/{project}/templates', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.search', ['POST'], 'api/projects/{project}/templates/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.store', ['POST'], 'api/projects/{project}/templates', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.templates.show', ['GET', 'HEAD'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.templates.update', ['PUT', 'PATCH'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.templates.destroy', ['DELETE'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.templates.associate', ['POST'], 'api/projects/{project}/templates/associate', DummyController::class.'@associate');
        $this->assertRouteRegistered('api.projects.templates.dissociate', ['DELETE'], 'api/projects/{project}/templates/{template?}/dissociate', DummyController::class.'@dissociate');

        $this->assertRouteRegistered('api.projects.templates.batchStore', ['POST'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.templates.batchUpdate', ['PATCH'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.templates.batchDestroy', ['DELETE'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.templates.restore');
        $this->assertRouteNotRegistered('api.projects.templates.batchRestore');
    }

    /** @test */
    public function registering_has_many_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasManyResource('projects', 'templates', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.templates.index', ['GET', 'HEAD'], 'api/projects/{project}/templates', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.search', ['POST'], 'api/projects/{project}/templates/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.store', ['POST'], 'api/projects/{project}/templates', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.templates.show', ['GET', 'HEAD'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.templates.update', ['PUT', 'PATCH'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.templates.destroy', ['DELETE'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.templates.restore', ['POST'], 'api/projects/{project}/templates/{template?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.templates.associate', ['POST'], 'api/projects/{project}/templates/associate', DummyController::class.'@associate');
        $this->assertRouteRegistered('api.projects.templates.dissociate', ['DELETE'], 'api/projects/{project}/templates/{template?}/dissociate', DummyController::class.'@dissociate');

        $this->assertRouteRegistered('api.projects.templates.batchStore', ['POST'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.templates.batchUpdate', ['PATCH'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.templates.batchDestroy', ['DELETE'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.templates.batchRestore', ['POST'], 'api/projects/{project}/templates/batch/restore', DummyController::class.'@batchRestore');
    }

    /** @test */
    public function registering_belongs_to_many_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::belongsToManyResource('projects', 'users', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.users.index', ['GET', 'HEAD'], 'api/projects/{project}/users', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.search', ['POST'], 'api/projects/{project}/users/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.store', ['POST'], 'api/projects/{project}/users', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.users.show', ['GET', 'HEAD'], 'api/projects/{project}/users/{user?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.users.update', ['PUT', 'PATCH'], 'api/projects/{project}/users/{user?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.users.destroy', ['DELETE'], 'api/projects/{project}/users/{user?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.users.sync', ['PATCH'], 'api/projects/{project}/users/sync', DummyController::class.'@sync');
        $this->assertRouteRegistered('api.projects.users.toggle', ['PATCH'], 'api/projects/{project}/users/toggle', DummyController::class.'@toggle');
        $this->assertRouteRegistered('api.projects.users.updatePivot', ['PATCH'], 'api/projects/{project}/users/{user?}/pivot', DummyController::class.'@updatePivot');
        $this->assertRouteRegistered('api.projects.users.attach', ['POST'], 'api/projects/{project}/users/attach', DummyController::class.'@attach');
        $this->assertRouteRegistered('api.projects.users.detach', ['DELETE'], 'api/projects/{project}/users/detach', DummyController::class.'@detach');

        $this->assertRouteRegistered('api.projects.users.batchStore', ['POST'], 'api/projects/{project}/users/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.users.batchUpdate', ['PATCH'], 'api/projects/{project}/users/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.users.batchDestroy', ['DELETE'], 'api/projects/{project}/users/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.users.restore');
        $this->assertRouteNotRegistered('api.projects.users.batchRestore');
    }

    /** @test */
    public function registering_belongs_to_many_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::belongsToManyResource('projects', 'users', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.users.index', ['GET', 'HEAD'], 'api/projects/{project}/users', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.search', ['POST'], 'api/projects/{project}/users/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.store', ['POST'], 'api/projects/{project}/users', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.users.show', ['GET', 'HEAD'], 'api/projects/{project}/users/{user?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.users.update', ['PUT', 'PATCH'], 'api/projects/{project}/users/{user?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.users.destroy', ['DELETE'], 'api/projects/{project}/users/{user?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.users.restore', ['POST'], 'api/projects/{project}/users/{user?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.users.sync', ['PATCH'], 'api/projects/{project}/users/sync', DummyController::class.'@sync');
        $this->assertRouteRegistered('api.projects.users.toggle', ['PATCH'], 'api/projects/{project}/users/toggle', DummyController::class.'@toggle');
        $this->assertRouteRegistered('api.projects.users.updatePivot', ['PATCH'], 'api/projects/{project}/users/{user?}/pivot', DummyController::class.'@updatePivot');
        $this->assertRouteRegistered('api.projects.users.attach', ['POST'], 'api/projects/{project}/users/attach', DummyController::class.'@attach');
        $this->assertRouteRegistered('api.projects.users.detach', ['DELETE'], 'api/projects/{project}/users/detach', DummyController::class.'@detach');

        $this->assertRouteRegistered('api.projects.users.batchStore', ['POST'], 'api/projects/{project}/users/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.users.batchUpdate', ['PATCH'], 'api/projects/{project}/users/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.users.batchDestroy', ['DELETE'], 'api/projects/{project}/users/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.users.batchRestore', ['POST'], 'api/projects/{project}/users/batch/restore', DummyController::class.'@batchRestore');
    }

    /** @test */
    public function registering_has_one_through_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasOneThroughResource('projects', 'meta', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.meta.store', ['POST'], 'api/projects/{project}/meta', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.meta.show', ['GET', 'HEAD'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.meta.update', ['PUT', 'PATCH'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.meta.destroy', ['DELETE'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.meta.batchStore', ['POST'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.meta.batchUpdate', ['PATCH'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.meta.batchDestroy', ['DELETE'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.meta.index');
        $this->assertRouteNotRegistered('api.projects.meta.search');
        $this->assertRouteNotRegistered('api.projects.meta.restore');
        $this->assertRouteNotRegistered('api.projects.meta.batchRestore');
    }

    /** @test */
    public function registering_has_one_through_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasOneThroughResource('projects', 'meta', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.meta.store', ['POST'], 'api/projects/{project}/meta', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.meta.show', ['GET', 'HEAD'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.meta.update', ['PUT', 'PATCH'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.meta.destroy', ['DELETE'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.meta.restore', ['POST'], 'api/projects/{project}/meta/{metum?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.meta.batchStore', ['POST'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.meta.batchUpdate', ['PATCH'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.meta.batchDestroy', ['DELETE'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.meta.batchRestore', ['POST'], 'api/projects/{project}/meta/batch/restore', DummyController::class.'@batchRestore');

        $this->assertRouteNotRegistered('api.projects.meta.index');
        $this->assertRouteNotRegistered('api.projects.meta.search');
    }

    /** @test */
    public function registering_has_many_through_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasManyThroughResource('projects', 'templates', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.templates.index', ['GET', 'HEAD'], 'api/projects/{project}/templates', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.search', ['POST'], 'api/projects/{project}/templates/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.store', ['POST'], 'api/projects/{project}/templates', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.templates.show', ['GET', 'HEAD'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.templates.update', ['PUT', 'PATCH'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.templates.destroy', ['DELETE'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.templates.associate', ['POST'], 'api/projects/{project}/templates/associate', DummyController::class.'@associate');
        $this->assertRouteRegistered('api.projects.templates.dissociate', ['DELETE'], 'api/projects/{project}/templates/{template?}/dissociate', DummyController::class.'@dissociate');

        $this->assertRouteRegistered('api.projects.templates.batchStore', ['POST'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.templates.batchUpdate', ['PATCH'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.templates.batchDestroy', ['DELETE'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.templates.restore');
        $this->assertRouteNotRegistered('api.projects.templates.batchRestore');
    }

    /** @test */
    public function registering_has_many_through_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::hasManyThroughResource('projects', 'templates', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.templates.index', ['GET', 'HEAD'], 'api/projects/{project}/templates', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.search', ['POST'], 'api/projects/{project}/templates/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.store', ['POST'], 'api/projects/{project}/templates', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.templates.show', ['GET', 'HEAD'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.templates.update', ['PUT', 'PATCH'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.templates.destroy', ['DELETE'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.templates.restore', ['POST'], 'api/projects/{project}/templates/{template?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.templates.associate', ['POST'], 'api/projects/{project}/templates/associate', DummyController::class.'@associate');
        $this->assertRouteRegistered('api.projects.templates.dissociate', ['DELETE'], 'api/projects/{project}/templates/{template?}/dissociate', DummyController::class.'@dissociate');

        $this->assertRouteRegistered('api.projects.templates.batchStore', ['POST'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.templates.batchUpdate', ['PATCH'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.templates.batchDestroy', ['DELETE'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.templates.batchRestore', ['POST'], 'api/projects/{project}/templates/batch/restore', DummyController::class.'@batchRestore');
    }

    /** @test */
    public function registering_morph_one_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphOneResource('projects', 'meta', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.meta.store', ['POST'], 'api/projects/{project}/meta', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.meta.show', ['GET', 'HEAD'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.meta.update', ['PUT', 'PATCH'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.meta.destroy', ['DELETE'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.meta.batchStore', ['POST'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.meta.batchUpdate', ['PATCH'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.meta.batchDestroy', ['DELETE'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.meta.index');
        $this->assertRouteNotRegistered('api.projects.meta.search');
        $this->assertRouteNotRegistered('api.projects.meta.restore');
        $this->assertRouteNotRegistered('api.projects.meta.batchRestore');
    }

    /** @test */
    public function registering_morph_one_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphOneResource('projects', 'meta', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.meta.store', ['POST'], 'api/projects/{project}/meta', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.meta.show', ['GET', 'HEAD'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.meta.update', ['PUT', 'PATCH'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.meta.destroy', ['DELETE'], 'api/projects/{project}/meta/{metum?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.meta.restore', ['POST'], 'api/projects/{project}/meta/{metum?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.meta.batchStore', ['POST'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.meta.batchUpdate', ['PATCH'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.meta.batchDestroy', ['DELETE'], 'api/projects/{project}/meta/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.meta.batchRestore', ['POST'], 'api/projects/{project}/meta/batch/restore', DummyController::class.'@batchRestore');

        $this->assertRouteNotRegistered('api.projects.meta.index');
        $this->assertRouteNotRegistered('api.projects.meta.search');
    }

    /** @test */
    public function registering_morph_many_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphManyResource('projects', 'templates', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.templates.index', ['GET', 'HEAD'], 'api/projects/{project}/templates', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.search', ['POST'], 'api/projects/{project}/templates/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.store', ['POST'], 'api/projects/{project}/templates', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.templates.show', ['GET', 'HEAD'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.templates.update', ['PUT', 'PATCH'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.templates.destroy', ['DELETE'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.templates.associate', ['POST'], 'api/projects/{project}/templates/associate', DummyController::class.'@associate');
        $this->assertRouteRegistered('api.projects.templates.dissociate', ['DELETE'], 'api/projects/{project}/templates/{template?}/dissociate', DummyController::class.'@dissociate');

        $this->assertRouteRegistered('api.projects.templates.batchStore', ['POST'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.templates.batchUpdate', ['PATCH'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.templates.batchDestroy', ['DELETE'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.templates.restore');
        $this->assertRouteNotRegistered('api.projects.templates.batchRestore');
    }

    /** @test */
    public function registering_morph_many_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphManyResource('projects', 'templates', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.templates.index', ['GET', 'HEAD'], 'api/projects/{project}/templates', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.search', ['POST'], 'api/projects/{project}/templates/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.templates.store', ['POST'], 'api/projects/{project}/templates', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.templates.show', ['GET', 'HEAD'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.templates.update', ['PUT', 'PATCH'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.templates.destroy', ['DELETE'], 'api/projects/{project}/templates/{template?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.templates.restore', ['POST'], 'api/projects/{project}/templates/{template?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.templates.associate', ['POST'], 'api/projects/{project}/templates/associate', DummyController::class.'@associate');
        $this->assertRouteRegistered('api.projects.templates.dissociate', ['DELETE'], 'api/projects/{project}/templates/{template?}/dissociate', DummyController::class.'@dissociate');

        $this->assertRouteRegistered('api.projects.templates.batchStore', ['POST'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.templates.batchUpdate', ['PATCH'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.templates.batchDestroy', ['DELETE'], 'api/projects/{project}/templates/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.templates.batchRestore', ['POST'], 'api/projects/{project}/templates/batch/restore', DummyController::class.'@batchRestore');
    }

    /** @test */
    public function registering_morph_to_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphToResource('projects', 'owner', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.owner.show', ['GET', 'HEAD'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.owner.update', ['PUT', 'PATCH'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.owner.destroy', ['DELETE'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.owner.batchUpdate', ['PATCH'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.owner.batchDestroy', ['DELETE'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.owner.store');
        $this->assertRouteNotRegistered('api.projects.owner.batchStore');
        $this->assertRouteNotRegistered('api.projects.owner.index');
        $this->assertRouteNotRegistered('api.projects.owner.search');
        $this->assertRouteNotRegistered('api.projects.owner.restore');
        $this->assertRouteNotRegistered('api.projects.owner.batchRestore');
    }

    /** @test */
    public function registering_morph_to_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphToResource('projects', 'owner', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.owner.show', ['GET', 'HEAD'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.owner.update', ['PUT', 'PATCH'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.owner.destroy', ['DELETE'], 'api/projects/{project}/owner/{owner?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.owner.restore', ['POST'], 'api/projects/{project}/owner/{owner?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.owner.batchUpdate', ['PATCH'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.owner.batchDestroy', ['DELETE'], 'api/projects/{project}/owner/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.owner.batchRestore', ['POST'], 'api/projects/{project}/owner/batch/restore', DummyController::class.'@batchRestore');

        $this->assertRouteNotRegistered('api.projects.owner.store');
        $this->assertRouteNotRegistered('api.projects.owner.batchStore');
        $this->assertRouteNotRegistered('api.projects.owner.index');
        $this->assertRouteNotRegistered('api.projects.owner.search');
    }

    /** @test */
    public function registering_morph_to_many_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphToManyResource('projects', 'users', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.users.index', ['GET', 'HEAD'], 'api/projects/{project}/users', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.search', ['POST'], 'api/projects/{project}/users/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.store', ['POST'], 'api/projects/{project}/users', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.users.show', ['GET', 'HEAD'], 'api/projects/{project}/users/{user?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.users.update', ['PUT', 'PATCH'], 'api/projects/{project}/users/{user?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.users.destroy', ['DELETE'], 'api/projects/{project}/users/{user?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.users.sync', ['PATCH'], 'api/projects/{project}/users/sync', DummyController::class.'@sync');
        $this->assertRouteRegistered('api.projects.users.toggle', ['PATCH'], 'api/projects/{project}/users/toggle', DummyController::class.'@toggle');
        $this->assertRouteRegistered('api.projects.users.updatePivot', ['PATCH'], 'api/projects/{project}/users/{user?}/pivot', DummyController::class.'@updatePivot');
        $this->assertRouteRegistered('api.projects.users.attach', ['POST'], 'api/projects/{project}/users/attach', DummyController::class.'@attach');
        $this->assertRouteRegistered('api.projects.users.detach', ['DELETE'], 'api/projects/{project}/users/detach', DummyController::class.'@detach');

        $this->assertRouteRegistered('api.projects.users.batchStore', ['POST'], 'api/projects/{project}/users/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.users.batchUpdate', ['PATCH'], 'api/projects/{project}/users/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.users.batchDestroy', ['DELETE'], 'api/projects/{project}/users/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.users.restore');
        $this->assertRouteNotRegistered('api.projects.users.batchRestore');
    }

    /** @test */
    public function registering_morph_to_many_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphToManyResource('projects', 'users', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.users.index', ['GET', 'HEAD'], 'api/projects/{project}/users', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.search', ['POST'], 'api/projects/{project}/users/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.store', ['POST'], 'api/projects/{project}/users', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.users.show', ['GET', 'HEAD'], 'api/projects/{project}/users/{user?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.users.update', ['PUT', 'PATCH'], 'api/projects/{project}/users/{user?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.users.destroy', ['DELETE'], 'api/projects/{project}/users/{user?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.users.restore', ['POST'], 'api/projects/{project}/users/{user?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.users.sync', ['PATCH'], 'api/projects/{project}/users/sync', DummyController::class.'@sync');
        $this->assertRouteRegistered('api.projects.users.toggle', ['PATCH'], 'api/projects/{project}/users/toggle', DummyController::class.'@toggle');
        $this->assertRouteRegistered('api.projects.users.updatePivot', ['PATCH'], 'api/projects/{project}/users/{user?}/pivot', DummyController::class.'@updatePivot');
        $this->assertRouteRegistered('api.projects.users.attach', ['POST'], 'api/projects/{project}/users/attach', DummyController::class.'@attach');
        $this->assertRouteRegistered('api.projects.users.detach', ['DELETE'], 'api/projects/{project}/users/detach', DummyController::class.'@detach');

        $this->assertRouteRegistered('api.projects.users.batchStore', ['POST'], 'api/projects/{project}/users/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.users.batchUpdate', ['PATCH'], 'api/projects/{project}/users/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.users.batchDestroy', ['DELETE'], 'api/projects/{project}/users/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.users.batchRestore', ['POST'], 'api/projects/{project}/users/batch/restore', DummyController::class.'@batchRestore');
    }

    /** @test */
    public function registering_morphed_by_many_resource()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphedByManyResource('projects', 'users', DummyController::class);
        });

        $this->assertRouteRegistered('api.projects.users.index', ['GET', 'HEAD'], 'api/projects/{project}/users', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.search', ['POST'], 'api/projects/{project}/users/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.store', ['POST'], 'api/projects/{project}/users', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.users.show', ['GET', 'HEAD'], 'api/projects/{project}/users/{user?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.users.update', ['PUT', 'PATCH'], 'api/projects/{project}/users/{user?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.users.destroy', ['DELETE'], 'api/projects/{project}/users/{user?}', DummyController::class.'@destroy');

        $this->assertRouteRegistered('api.projects.users.sync', ['PATCH'], 'api/projects/{project}/users/sync', DummyController::class.'@sync');
        $this->assertRouteRegistered('api.projects.users.toggle', ['PATCH'], 'api/projects/{project}/users/toggle', DummyController::class.'@toggle');
        $this->assertRouteRegistered('api.projects.users.updatePivot', ['PATCH'], 'api/projects/{project}/users/{user?}/pivot', DummyController::class.'@updatePivot');
        $this->assertRouteRegistered('api.projects.users.attach', ['POST'], 'api/projects/{project}/users/attach', DummyController::class.'@attach');
        $this->assertRouteRegistered('api.projects.users.detach', ['DELETE'], 'api/projects/{project}/users/detach', DummyController::class.'@detach');

        $this->assertRouteRegistered('api.projects.users.batchStore', ['POST'], 'api/projects/{project}/users/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.users.batchUpdate', ['PATCH'], 'api/projects/{project}/users/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.users.batchDestroy', ['DELETE'], 'api/projects/{project}/users/batch', DummyController::class.'@batchDestroy');

        $this->assertRouteNotRegistered('api.projects.users.restore');
        $this->assertRouteNotRegistered('api.projects.users.batchRestore');
    }

    /** @test */
    public function registering_morphed_by_many_resource_with_soft_deletes()
    {
        Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
            Orion::morphedByManyResource('projects', 'users', DummyController::class)->withSoftDeletes();
        });

        $this->assertRouteRegistered('api.projects.users.index', ['GET', 'HEAD'], 'api/projects/{project}/users', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.search', ['POST'], 'api/projects/{project}/users/search', DummyController::class.'@index');
        $this->assertRouteRegistered('api.projects.users.store', ['POST'], 'api/projects/{project}/users', DummyController::class.'@store');
        $this->assertRouteRegistered('api.projects.users.show', ['GET', 'HEAD'], 'api/projects/{project}/users/{user?}', DummyController::class.'@show');
        $this->assertRouteRegistered('api.projects.users.update', ['PUT', 'PATCH'], 'api/projects/{project}/users/{user?}', DummyController::class.'@update');
        $this->assertRouteRegistered('api.projects.users.destroy', ['DELETE'], 'api/projects/{project}/users/{user?}', DummyController::class.'@destroy');
        $this->assertRouteRegistered('api.projects.users.restore', ['POST'], 'api/projects/{project}/users/{user?}/restore', DummyController::class.'@restore');

        $this->assertRouteRegistered('api.projects.users.sync', ['PATCH'], 'api/projects/{project}/users/sync', DummyController::class.'@sync');
        $this->assertRouteRegistered('api.projects.users.toggle', ['PATCH'], 'api/projects/{project}/users/toggle', DummyController::class.'@toggle');
        $this->assertRouteRegistered('api.projects.users.updatePivot', ['PATCH'], 'api/projects/{project}/users/{user?}/pivot', DummyController::class.'@updatePivot');
        $this->assertRouteRegistered('api.projects.users.attach', ['POST'], 'api/projects/{project}/users/attach', DummyController::class.'@attach');
        $this->assertRouteRegistered('api.projects.users.detach', ['DELETE'], 'api/projects/{project}/users/detach', DummyController::class.'@detach');

        $this->assertRouteRegistered('api.projects.users.batchStore', ['POST'], 'api/projects/{project}/users/batch', DummyController::class.'@batchStore');
        $this->assertRouteRegistered('api.projects.users.batchUpdate', ['PATCH'], 'api/projects/{project}/users/batch', DummyController::class.'@batchUpdate');
        $this->assertRouteRegistered('api.projects.users.batchDestroy', ['DELETE'], 'api/projects/{project}/users/batch', DummyController::class.'@batchDestroy');
        $this->assertRouteRegistered('api.projects.users.batchRestore', ['POST'], 'api/projects/{project}/users/batch/restore', DummyController::class.'@batchRestore');
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

        if (!$route) {
            $this->fail("Route \"$name\" with uri \"{$uri}\" does not exist.");
        }
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