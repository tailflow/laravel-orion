<?php

use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;
use Orion\Tests\Fixtures\App\Http\Controllers\HistoryController;
use Orion\Tests\Fixtures\App\Http\Controllers\PostsController;
use Orion\Tests\Fixtures\App\Http\Controllers\SuppliersController;
use Orion\Tests\Fixtures\App\Http\Controllers\TagMetaController;
use Orion\Tests\Fixtures\App\Http\Controllers\TagsController;
use Orion\Tests\Fixtures\App\Http\Controllers\TeamsController;

Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
    Orion::resource('tags', TagsController::class);
    Orion::resource('tag_meta', TagMetaController::class)->withSoftDeletes();
    Orion::resource('teams', TeamsController::class)->withSoftDeletes();
    Orion::resource('suppliers', SuppliersController::class);
    Orion::resource('history', HistoryController::class)->withSoftDeletes();
    Orion::resource('posts', PostsController::class);
});
