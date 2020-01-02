<?php

namespace Orion;

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

    /**
     * Registers new standard resource.
     *
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public static function resource($name, $controller, $options = [])
    {
        if (Arr::get($options, 'softDeletes')) {
            $paramName = Str::singular($name);
            Route::post("{$name}/{{$paramName}}/restore", $controller.'@restore')->name("$name.restore");
        }
        Route::post("{$name}/search", $controller.'@index')->name("$name.search");

        return Route::apiResource($name, $controller, $options);
    }

    /**
     * Registers new relation resource.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param string $relationType
     * @param array $options
     * @return bool
     */
    public static function resourceRelation($resource, $relation, $controller, $relationType, $options = [])
    {
        $resourceParamName = Str::singular($resource);
        $relationParamName = Str::singular($relation);

        $baseUri = "{$resource}/{{$resourceParamName}}/{$relation}";
        $completeUri = "$baseUri/{{$relationParamName}?}";

        if (!in_array($relationType, [HasOne::class, BelongsTo::class, HasOneThrough::class, MorphOne::class], true)) {
            Route::get($baseUri, $controller.'@index')->name("$resource.relation.$relation.index");
            Route::post("$baseUri/search", $controller.'@index')->name("$resource.relation.$relation.search");
            $completeUri = "$baseUri/{{$relationParamName}}";
        }

        if ($relationType !== BelongsTo::class) {
            Route::post($baseUri, $controller.'@store')->name("$resource.relation.$relation.store");
        }

        Route::get($completeUri, $controller.'@show')->name("$resource.relation.$relation.show");
        Route::patch($completeUri, $controller.'@update')->name("$resource.relation.$relation.update");
        Route::put($completeUri, $controller.'@update')->name("$resource.relation.$relation.update");
        Route::delete($completeUri, $controller.'@destroy')->name("$resource.relation.$relation.destroy");

        if (Arr::get($options, 'softDeletes')) {
            Route::post("{$completeUri}/restore", $controller.'@restore')->name("$resource.relation.$relation.restore");
        }

        if (in_array($relationType, [HasMany::class, HasManyThrough::class, MorphMany::class], true)) {
            Route::post("{$baseUri}/associate", $controller.'@associate')->name("$resource.relation.$relation.associate");
            Route::delete("{$completeUri}/dissociate", $controller.'@dissociate')->name("$resource.relation.$relation.dissociate");
        }

        if (in_array($relationType, [BelongsToMany::class, MorphToMany::class], true)) {
            Route::patch("{$baseUri}/sync", $controller.'@sync')->name("$resource.relation.$relation.sync");
            Route::patch("{$baseUri}/toggle", $controller.'@toggle')->name("$resource.relation.$relation.toggle");
            Route::patch("{$completeUri}/pivot", $controller.'@updatePivot')->name("$resource.relation.$relation.pivot");
            Route::post("{$baseUri}/attach", $controller.'@attach')->name("$resource.relation.$relation.attach");
            Route::delete("{$baseUri}/detach", $controller.'@detach')->name("$resource.relation.$relation.detach");
        }

        return true;
    }

    /**
     * Register new resource for "hasOne" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function hasOneResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasOne::class, $options);
    }

    /**
     * Register new resource for "belongsTo" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function belongsToResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, BelongsTo::class, $options);
    }

    /**
     * Register new resource for "hasMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function hasManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasMany::class, $options);
    }

    /**
     * Register new resource for "belongsToMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function belongsToManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, BelongsToMany::class, $options);
    }

    /**
     * Register new resource for "hasOneThrough" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function hasOneThroughResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasOneThrough::class, $options);
    }

    /**
     * Register new resource for "hasManyThrough" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function hasManyThroughResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, HasManyThrough::class, $options);
    }

    /**
     * Register new resource for "morphOne" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function morphOneResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, MorphOne::class, $options);
    }

    /**
     * Register new resource for "morphMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function morphManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, MorphMany::class, $options);
    }

    /**
     * Register new resource for "morphToMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return bool
     */
    public static function morphToManyResource($resource, $relation, $controller, $options = [])
    {
        return static::resourceRelation($resource, $relation, $controller, MorphToMany::class, $options);
    }
}
