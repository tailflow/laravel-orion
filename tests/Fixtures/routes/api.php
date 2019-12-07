<?php

use Orion\Tests\Fixtures\App\Http\Controllers\SuppliersController;
use Orion\Tests\Fixtures\App\Http\Controllers\TeamsController;
use Orion\Tests\Fixtures\App\Http\Controllers\TagsController;
use Illuminate\Support\Facades\Route;
use Orion\Orion;

Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
    Orion::resource('tags', TagsController::class);
    Orion::resource('teams', TeamsController::class);
    Orion::resource('suppliers', SuppliersController::class);
});
