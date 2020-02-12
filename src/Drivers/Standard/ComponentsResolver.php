<?php

namespace Orion\Drivers\Standard;

use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Facades\App;
use Orion\Http\Requests\Request;

class ComponentsResolver implements \Orion\Contracts\ComponentsResolver
{
    /**
     * @var string $resourceModelClass
     */
    protected $resourceModelClass;


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
        $requestClassName = 'App\\Http\\Requests\\'.class_basename($this->resourceModelClass).'Request';

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
        $resourceClassName = 'App\\Http\\Resources\\'.class_basename($this->resourceModelClass).'Resource';

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
        $collectionResourceClassName = 'App\\Http\\Resources\\'.class_basename($this->resourceModelClass).'CollectionResource';

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
}
