<?php

namespace Orion\Drivers\Standard;

use Illuminate\Support\Facades\App;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;

class ComponentsResolver implements \Orion\Contracts\ComponentsResolver
{
    /**
     * @var string $resourceModelClass
     */
    protected $resourceModelClass;

    /**
     * @var string $requestClassesNamespace
     */
    protected $requestClassesNamespace = 'App\\Http\\Requests\\';

    /**
     * @var string $resourceClassesNamespace
     */
    protected $resourceClassesNamespace = 'App\\Http\\Resources\\';

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
     * Guesses collection resource class based on the resource model.
     */
    public function resolveCollectionResourceClass(): ?string
    {
        $collectionResourceClassName = $this->getResourceClassesNamespace().class_basename($this->resourceModelClass).'CollectionResource';

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

    /**
     * @param string $requestClassesNamespace
     * @return $this
     */
    public function setRequestClassesNamespace(string $requestClassesNamespace): self
    {
        $this->requestClassesNamespace = $requestClassesNamespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestClassesNamespace(): string
    {
        return $this->requestClassesNamespace;
    }

    /**
     * @param string $resourceClassesNamespace
     * @return $this
     */
    public function setResourceClassesNamespace(string $resourceClassesNamespace): self
    {
        $this->resourceClassesNamespace = $resourceClassesNamespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getResourceClassesNamespace(): string
    {
        return $this->resourceClassesNamespace;
    }
}
