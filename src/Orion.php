<?php

namespace Laralord\Orion;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Route;

class Orion
{
    public static function resource($name, $controller, $options = [])
    {
        return Route::apiResource($name, $controller, $options);
    }

    public static function resourceRelation($resource, $relation, $controller, $relationType)
    {
        $resourceName = str_singular($resource);

        if (!in_array($relationType, [HasOne::class, MorphOne::class], true)) {
            if (!in_array($relationType, [HasMany::class, MorphMany::class], true)) {
                Route::patch("{$resource}/{{$resourceName}}/{$relation}/sync", $controller.'@sync')->name("$resource.relation.$relation.sync");
                Route::patch("{$resource}/{{$resourceName}}/{$relation}/toggle", $controller.'@toggle')->name("$resource.relation.$relation.toggle");
                Route::patch("{$resource}/{{$resourceName}}/{$relation}/{{$relation}}/pivot", $controller.'@updatePivot')->name("$resource.relation.$relation.pivot");
            }
            Route::post("{$resource}/{{$resourceName}}/{$relation}/attach", $controller.'@attach')->name("$resource.relation.$relation.attach");
            Route::delete("{$resource}/{{$resourceName}}/{$relation}/detach", $controller.'@detach')->name("$resource.relation.$relation.detach");
        }

        if (!in_array($relationType, [HasOne::class, MorphOne::class], true)) {
            Route::get("{$resource}/{{$resourceName}}/{$relation}", $controller.'@index')->name("$resource.relation.$relation.index");
        }
        Route::post("{$resource}/{{$resourceName}}/{$relation}", $controller.'@store')->name("$resource.relation.$relation.store");
        Route::get("{$resource}/{{$resourceName}}/{$relation}/{{$relation}?}", $controller.'@show')->name("$resource.relation.$relation.show");
        Route::patch("{$resource}/{{$resourceName}}/{$relation}/{{$relation}?}", $controller.'@update')->name("$resource.relation.$relation.update");
        Route::put("{$resource}/{{$resourceName}}/{$relation}/{{$relation}?}", $controller.'@update')->name("$resource.relation.$relation.update");
        Route::delete("{$resource}/{{$resourceName}}/{$relation}/{{$relation}?}", $controller.'@destroy')->name("$resource.relation.$relation.destroy");

        return true;
    }

    public static function hasOneResource($resource, $relation, $controller)
    {
        return static::resourceRelation($resource, $relation, $controller, HasOne::class);
    }

    public static function hasManyResource($resource, $relation, $controller)
    {
        return static::resourceRelation($resource, $relation, $controller, HasMany::class);
    }

    public static function belongsToManyResource($resource, $relation, $controller)
    {
        return static::resourceRelation($resource, $relation, $controller, BelongsToMany::class);
    }

    public static function morphOneResource($resource, $relation, $controller)
    {
        return static::resourceRelation($resource, $relation, $controller, MorphOne::class);
    }

    public static function morphManyResource($resource, $relation, $controller)
    {
        return static::resourceRelation($resource, $relation, $controller, MorphMany::class);
    }

    public static function morphToManyResource($resource, $relation, $controller)
    {
        return static::resourceRelation($resource, $relation, $controller, MorphToMany::class);
    }
}
