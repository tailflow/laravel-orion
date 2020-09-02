<?php

use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;
use Orion\Tests\Fixtures\App\Http\Controllers\PostsController;
use Orion\Tests\Fixtures\App\Http\Controllers\TeamsController;

Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
    Orion::resource('teams', TeamsController::class);
    Orion::resource('posts', PostsController::class)->withSoftDeletes();
});
