<?php

namespace Laralord\Orion;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Orion
{
    //TODO: use own registrar to define both resources and relation resources

    public static function resource($name, $controller, $options = [])
    {
        if (Arr::get($options, 'softDeletes')) {
            $paramName = Str::singular($name);
            Route::post("{$name}/{{$paramName}}/restore", $controller.'@restore')->name("$name.restore");
        }

        return Route::apiResource($name, $controller, $options);
    }

    public static function resourceRelation($resource, $relation, $controller, $relationType, $options = [])
    {
        //TODO: make buildRelationUri method and mark the last parameter as optional only for one-to-one relations

        $resourceParamName = Str::singular($resource);
        $relationParamName = Str::singular($relation);

        if (!in_array($relationType, [HasOne::class, BelongsTo::class, HasOneThrough::class, MorphOne::class], true)) {
            Route::get("{$resource}/{{$resourceParamName}}/{$relation}", $controller.'@index')->name("$resource.relation.$relation.index");
        }

        if ($relationType !== BelongsTo::class) {
            Route::post("{$resource}/{{$resourceParamName}}/{$relation}", $controller.'@store')->name("$resource.relation.$relation.store");
        }

        Route::get("{$resource}/{{$resourceParamName}}/{$relation}/{{$relationParamName}?}", $controller.'@show')->name("$resource.relation.$relation.show");
        Route::patch("{$resource}/{{$resourceParamName}}/{$relation}/{{$relationParamName}?}", $controller.'@update')->name("$resource.relation.$relation.update");
        Route::put("{$resource}/{{$resourceParamName}}/{$relation}/{{$relationParamName}?}", $controller.'@update')->name("$resource.relation.$relation.update");
        Route::delete("{$resource}/{{$resourceParamName}}/{$relation}/{{$relationParamName}?}", $controller.'@destroy')->name("$resource.relation.$relation.destroy");

        if (Arr::get($options, 'softDeletes')) {
            Route::post("{$resource}/{{$resourceParamName}}/{$relation}/{{$relationParamName}?}/restore", $controller.'@restore')->name("$resource.relation.$relation.restore");
        }

        if (in_array($relationType, [HasMany::class, HasManyThrough::class, MorphMany::class], true)) {
            Route::post("{$resource}/{{$resourceParamName}}/{$relation}/associate", $controller.'@associate')->name("$resource.relation.$relation.associate");
            Route::delete("{$resource}/{{$resourceParamName}}/{$relation}/{{$relationParamName}}/dissociate", $controller.'@dissociate')->name("$resource.relation.$relation.dissociate");
        }

        if (in_array($relationType, [BelongsToMany::class, MorphToMany::class], true)) {
            Route::patch("{$resource}/{{$resourceParamName}}/{$relation}/sync", $controller.'@sync')->name("$resource.relation.$relation.sync");
            Route::patch("{$resource}/{{$resourceParamName}}/{$relation}/toggle", $controller.'@toggle')->name("$resource.relation.$relation.toggle");
            Route::patch("{$resource}/{{$resourceParamName}}/{$relation}/{{$relationParamName}}/pivot", $controller.'@updatePivot')->name("$resource.relation.$relation.pivot");
            Route::post("{$resource}/{{$resourceParamName}}/{$relation}/attach", $controller.'@attach')->name("$resource.relation.$relation.attach");
            Route::delete("{$resource}/{{$resourceParamName}}/{$relation}/detach", $controller.'@detach')->name("$resource.relation.$relation.detach");
        }

        return true;
    }

    public static function hasOneResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasOne::class, $options);
    }

    public static function belongsToResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, BelongsTo::class, $options);
    }

    public static function hasManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasMany::class, $options);
    }

    public static function belongsToManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, BelongsToMany::class, $options);
    }

    public static function hasOneThrough($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasOneThrough::class, $options);
    }

    public static function hasManyThroughResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasManyThrough::class, $options);
    }

    public static function morphOneResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, MorphOne::class, $options);
    }

    public static function morphManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, MorphMany::class, $options);
    }

    public static function morphToManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, MorphToMany::class, $options);
    }
}
