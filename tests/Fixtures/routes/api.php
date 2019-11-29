<?php

use Illuminate\Support\Facades\Route;
use Orion\Orion;

Route::group(['as' => 'api.', 'prefix' => 'api'], function () {
    Orion::resource('tags', 'Orion\Tests\Fixtures\App\Http\Controllers\TagsController');
    Orion::resource('teams', 'Orion\Tests\Fixtures\App\Http\Controllers\TeamsController');
});
