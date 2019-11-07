<?php

use Illuminate\Support\Facades\Route;
use Orion\Orion;

Route::group(['as' => 'api.', 'prefix' => 'api'], function() {
    Orion::resource('model_without_relations', 'Orion\Tests\Fixtures\App\Http\Controllers\ModelWithoutRelationsController');
});
