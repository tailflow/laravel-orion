<?php


namespace Laralord\Orion;


class Orion
{
    public static function resource($name, $controller, $options = [])
    {
        \Illuminate\Support\Facades\Route::apiResource($name, $controller, $options);
    }

    public static function resourceRelation($resource, $relation, $controller)
    {
        $resourceName = str_singular($resource);

        \Illuminate\Support\Facades\Route::patch("{$resource}/{{$resourceName}}/{$relation}/sync", $controller. '@sync')->name("$resource.relation.$relation.sync");
        \Illuminate\Support\Facades\Route::patch("{$resource}/{{$resourceName}}/{$relation}/toggle", $controller. '@toggle')->name("$resource.relation.$relation.toggle");
        \Illuminate\Support\Facades\Route::patch("{$resource}/{{$resourceName}}/{$relation}/{{$relation}}/pivot", $controller. '@updatePivot')->name("$resource.relation.$relation.pivot");
        \Illuminate\Support\Facades\Route::post("{$resource}/{{$resourceName}}/{$relation}/attach", $controller. '@attach')->name("$resource.relation.$relation.attach");
        \Illuminate\Support\Facades\Route::delete("{$resource}/{{$resourceName}}/{$relation}/detach", $controller. '@detach')->name("$resource.relation.$relation.detach");

        \Illuminate\Support\Facades\Route::get("{$resource}/{{$resourceName}}/{$relation}", $controller. '@index')->name("$resource.relation.$relation.index");
        \Illuminate\Support\Facades\Route::post("{$resource}/{{$resourceName}}/{$relation}", $controller. '@store')->name("$resource.relation.$relation.store");
        \Illuminate\Support\Facades\Route::get("{$resource}/{{$resourceName}}/{$relation}/{{$relation}}", $controller. '@show')->name("$resource.relation.$relation.show");
        \Illuminate\Support\Facades\Route::patch("{$resource}/{{$resourceName}}/{$relation}/{{$relation}}", $controller. '@update')->name("$resource.relation.$relation.update");
        \Illuminate\Support\Facades\Route::put("{$resource}/{{$resourceName}}/{$relation}/{{$relation}}", $controller. '@update')->name("$resource.relation.$relation.update");
        \Illuminate\Support\Facades\Route::delete("{$resource}/{{$resourceName}}/{$relation}/{{$relation}}", $controller. '@destroy')->name("$resource.relation.$relation.destroy");


    }
}
