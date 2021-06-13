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
use Orion\Specs\ResourcesCacheStore;

class Orion
{
    /**
     * @param ResourceRegistrar $registrar
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return PendingResourceRegistration
     */
    protected function makePendingResourceRegistration(ResourceRegistrar $registrar, string $name, string $controller, array $options): PendingResourceRegistration
    {
        return new PendingResourceRegistration(
            $registrar, $name, $controller, $options
        );
    }

    /**
     * Retrieves resource registrar from the container.
     *
     * @param string $registrarClass
     * @return ResourceRegistrar
     * @throws BindingResolutionException
     */
    protected function resolveRegistrar(string $registrarClass): ResourceRegistrar
    {
        if (app()->bound($registrarClass)) {
            return app()->make($registrarClass);
        }

        return new $registrarClass(app('router'), $this->resolveResourcesCacheStore());
    }

    /**
     * Retrieves resources cache store from the container.
     *
     * @return ResourcesCacheStore
     * @throws BindingResolutionException
     */
    protected function resolveResourcesCacheStore(): ResourcesCacheStore
    {
        if (app()->bound(ResourcesCacheStore::class)) {
            return app()->make(ResourcesCacheStore::class);
        }

        throw new BindingResolutionException('ResourcesCacheStore is not bound to the container');
    }

    /**
     * Registers new standard resource.
     *
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     * @throws BindingResolutionException
     */
    public function resource(string $name, string $controller, array $options = [])
    {
        $registrar = $this->resolveRegistrar(ResourceRegistrar::class);

        return $this->makePendingResourceRegistration($registrar, $name, $controller, $options);
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
    public function hasOneResource(string $resource, string $relation, string $controller, array $options = [])
    {
        $registrar = $this->resolveRegistrar(HasOneRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function belongsToResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(BelongsToRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function hasManyResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(HasManyRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function belongsToManyResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(BelongsToManyRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function hasOneThroughResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(HasOneThroughRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function hasManyThroughResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(HasManyThroughRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function morphOneResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(MorphOneRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function morphManyResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(MorphManyRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function morphToResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(MorphToRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function morphToManyResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(MorphToManyRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
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
    public function morphedByManyResource(string $resource, string $relation, string $controller, array $options = []): PendingResourceRegistration
    {
        $registrar = $this->resolveRegistrar(MorphToManyRelationResourceRegistrar::class);

        return $this->makePendingResourceRegistration(
            $registrar, "{$resource}.{$relation}", $controller, $options
        );
    }
}
