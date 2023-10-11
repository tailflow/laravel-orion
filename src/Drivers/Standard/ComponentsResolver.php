<?php

declare(strict_types=1);

namespace Orion\Drivers\Standard;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;
use Orion\Repositories\Repository;
use Orion\Repositories\BaseRepository;

class ComponentsResolver implements \Orion\Contracts\ComponentsResolver
{
    protected string $resourceModelClass;

    /**
     * ComponentsResolver constructor.
     *
     * @param string $resourceModelClass
     */
    public function __construct(string $resourceModelClass)
    {
        $this->resourceModelClass = $resourceModelClass;
    }

    /**
     * @return string
     */
    public function getRepositoryClassesNamespace(): string
    {
        return config('orion.namespaces.repositories');
    }

    /**
     * @return string
     */
    public function getRequestClassesNamespace(): string
    {
        return config('orion.namespaces.requests');
    }

    /**
     * Guesses request class based on the resource model.
     */
    public function resolveRepositoryClass(): string
    {
        $repositoryClassName = $this->getRepositoryClassesNamespace().class_basename(
                $this->resourceModelClass
            ).'Repository';

        if (class_exists($repositoryClassName)) {
            return $repositoryClassName;
        }

        return Repository::class;
    }

    /**
     * Guesses request class based on the resource model.
     */
    public function resolveRequestClass(): string
    {
        $requestClassName = $this->getRequestClassesNamespace().class_basename($this->resourceModelClass).'Request';

        if (class_exists($requestClassName)) {
            return $requestClassName;
        }

        return Request::class;
    }

    /**
     * Guesses resource class based on the resource model.
     */
    public function resolveResourceClass(): string
    {
        $resourceClassName = $this->getResourceClassesNamespace().class_basename($this->resourceModelClass).'Resource';

        if (class_exists($resourceClassName)) {
            return $resourceClassName;
        }

        return Resource::class;
    }

    /**
     * @return string
     */
    public function getResourceClassesNamespace(): string
    {
        return config('orion.namespaces.resources');
    }

    /**
     * Guesses collection resource class based on the resource model.
     */
    public function resolveCollectionResourceClass(): ?string
    {
        $collectionResourceClassName = $this->getResourceClassesNamespace().class_basename(
                $this->resourceModelClass
            ).'CollectionResource';

        if (class_exists($collectionResourceClassName)) {
            return $collectionResourceClassName;
        }

        return null;
    }

    /**
     * Contextually binds resolved request class on current controller.
     *
     * @param string $requestClass
     */
    public function bindRequestClass(string $requestClass): void
    {
        App::bind(Request::class, $requestClass);
    }

    public function bindPolicyClass(string $policyClass): void
    {
        Gate::policy($this->resourceModelClass, $policyClass);
    }

    /**
     * @param string $repositoryClass
     * @return BaseRepository
     * @throws BindingResolutionException
     */
    public function instantiateRepository(string $repositoryClass): BaseRepository
    {
        $repository = app()->make($repositoryClass);

        if ($repository instanceof Repository) {
            $repository->setModel($this->resourceModelClass);
        }

        return $repository;
    }
}
