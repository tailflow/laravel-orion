<?php

namespace Orion;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Http\Routing\BelongsToManyRelationResourceRegistrar;
use Orion\Http\Routing\BelongsToRelationResourceRegistrar;
use Orion\Http\Routing\HasManyRelationResourceRegistrar;
use Orion\Http\Routing\HasManyThroughRelationResourceRegistrar;
use Orion\Http\Routing\HasOneRelationResourceRegistrar;
use Orion\Http\Routing\HasOneThroughRelationResourceRegistrar;
use Orion\Http\Routing\MorphManyRelationResourceRegistrar;
use Orion\Http\Routing\MorphOneRelationResourceRegistrar;
use Orion\Http\Routing\MorphToManyRelationResourceRegistrar;
use Orion\Http\Routing\MorphToRelationResourceRegistrar;
use Orion\Http\Routing\PendingResourceRegistration;
use Orion\Http\Routing\ResourceRegistrar;

class Orion
{
    /**
     * Registers new standard resource.
     *
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function resource($name, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(ResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, $name, $controller, $options
        );
    }

    /**
     * Register new resource for "hasOne" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function hasOneResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(HasOneRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "belongsTo" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function belongsToResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(BelongsToRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "hasMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function hasManyResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(HasManyRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "belongsToMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function belongsToManyResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(BelongsToManyRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "hasOneThrough" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function hasOneThroughResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(HasOneThroughRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "hasManyThrough" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function hasManyThroughResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(HasManyThroughRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "morphOne" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function morphOneResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(MorphOneRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "morphMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function morphManyResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(MorphManyRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "morphTo" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function morphToResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(MorphToRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "morphToMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function morphToManyResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(MorphToManyRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Register new resource for "morphedByMany" relation.
     *
     * @param string $resource
     * @param string $relation
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function morphedByManyResource($resource, $relation, $controller, $options = [])
    {
        $registrar = $this->resolveRegistrar(MorphToManyRelationResourceRegistrar::class);

        return new PendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }

    /**
     * Retrieves resource registrar from the container.
     *
     * @param string $registrarClass
     * @return ResourceRegistrar
     * @throws BindingResolutionException
     */
    protected function resolveRegistrar($registrarClass)
    {
        if (app()->bound($registrarClass)) {
            return app()->make($registrarClass);
        }

        return new $registrarClass(app('router'));
    }
}
