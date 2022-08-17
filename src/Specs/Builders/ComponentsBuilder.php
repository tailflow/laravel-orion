<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Orion\Specs\Builders\Components\Model\BaseModelComponentBuilder;
use Orion\Specs\Builders\Components\Model\ModelResourceComponentBuilder;
use Orion\Specs\Builders\Components\Shared\ResourceLinksComponentBuilder;
use Orion\Specs\Builders\Components\Shared\ResourceMetaComponentBuilder;
use Orion\Specs\Builders\Components\Shared\SecurityComponentBuilder;
use Orion\Specs\ResourcesCacheStore;

class ComponentsBuilder
{
    /**
     * @var ResourcesCacheStore
     */
    protected $resourcesCacheStore;

    /**
     * @const array SCHEMA_MODEL_COMPONENT_BUILDERS
     */
    protected const SCHEMA_MODEL_COMPONENT_BUILDERS = [
        BaseModelComponentBuilder::class,
        ModelResourceComponentBuilder::class,
    ];

    /**
     * @const array SCHEMA_SHARED_COMPONENT_BUILDERS
     */
    protected const SCHEMA_SHARED_COMPONENT_BUILDERS = [
        ResourceLinksComponentBuilder::class,
        ResourceMetaComponentBuilder::class,
    ];

    protected const ROOT_COMPONENT_BUILDERS = [
        SecurityComponentBuilder::class
    ];

    /**
     * ComponentsBuilder constructor.
     *
     * @param ResourcesCacheStore $resourcesCacheStore
     */
    public function __construct(ResourcesCacheStore $resourcesCacheStore)
    {
        $this->resourcesCacheStore = $resourcesCacheStore;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    public function build(): array
    {
        $resources = $this->resourcesCacheStore->getResources();

        $components = collect([]);

        $components = $this->buildRootComponents($components);
        $components = $this->buildModelComponents($components, $resources);
        $components = $this->buildSharedComponents($components);

        return $components->toArray();
    }


    /**
     * @param Collection $components
     * @return Collection
     * @throws BindingResolutionException
     */
    protected function buildRootComponents(Collection $components): Collection
    {
        foreach (static::ROOT_COMPONENT_BUILDERS as $sharedComponentBuilderClass) {
            $sharedComponentBuilder = app()->make($sharedComponentBuilderClass);

            $sharedComponent = $sharedComponentBuilder->build();

            $components->put($sharedComponent->title, $sharedComponent);
        }

        return $components;
    }

    /**
     * @param Collection $components
     * @param array $resources
     * @return Collection
     * @throws BindingResolutionException
     */
    protected function buildModelComponents(Collection $components, array $resources): Collection
    {
        $schemas = $components->get('schemas', []);

        foreach ($resources as $resource) {
            $resourceModelClass = app()->make($resource->controller)->resolveResourceModelClass();
            $resourceModel = app()->make($resourceModelClass);

            foreach (static::SCHEMA_MODEL_COMPONENT_BUILDERS as $modelComponentBuilderClass) {
                $modelComponentBuilder = app()->make($modelComponentBuilderClass);

                $modelComponent = $modelComponentBuilder->build($resourceModel);

                $schemas[$modelComponent->title] = $modelComponent->toArray();
            }
        }

        $components->put('schemas', $schemas);

        return $components;
    }

    /**
     * @param Collection $components
     * @return Collection
     * @throws BindingResolutionException
     */
    protected function buildSharedComponents(Collection $components): Collection
    {
        $schemas = $components->get('schemas', []);

        foreach (static::SCHEMA_SHARED_COMPONENT_BUILDERS as $sharedComponentBuilderClass) {
            $sharedComponentBuilder = app()->make($sharedComponentBuilderClass);

            $sharedComponent = $sharedComponentBuilder->build();

            $schemas[$sharedComponent->title] = $sharedComponent->toArray();
        }

        $components->put('schemas', $schemas);

        return $components;
    }
}
