<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Orion\Specs\Builders\Components\Model\BaseModelComponentBuilder;
use Orion\Specs\Builders\Components\Model\ModelResourceComponentBuilder;
use Orion\Specs\Builders\Components\Shared\ResourceLinksComponentBuilder;
use Orion\Specs\Builders\Components\Shared\ResourceMetaComponentBuilder;
use Orion\Specs\ResourcesCacheStore;

class ComponentsBuilder
{
    /**
     * @var ResourcesCacheStore
     */
    protected $resourcesCacheStore;

    /**
     * @const array MODEL_COMPONENT_BUILDERS
     */
    protected const MODEL_COMPONENT_BUILDERS = [
        BaseModelComponentBuilder::class,
        ModelResourceComponentBuilder::class,
    ];

    /**
     * @const array SHARED_COMPONENT_BUILDERS
     */
    protected const SHARED_COMPONENT_BUILDERS = [
        ResourceLinksComponentBuilder::class,
        ResourceMetaComponentBuilder::class,
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

        $components = $this->buildModelComponents($components, $resources);
        $components = $this->buildSharedComponents($components);

        return $components->toArray();
    }

    /**
     * @param Collection $components
     * @param array $resources
     * @return Collection
     * @throws BindingResolutionException
     */
    protected function buildModelComponents(Collection $components, array $resources): Collection
    {
        foreach ($resources as $resource) {
            $resourceModelClass = app()->make($resource->controller)->resolveResourceModelClass();
            $resourceModel = app()->make($resourceModelClass);

            foreach (static::MODEL_COMPONENT_BUILDERS as $modelComponentBuilderClass) {
                $modelComponentBuilder = app()->make($modelComponentBuilderClass);

                $modelComponent = $modelComponentBuilder->build($resourceModel);

                $components->put($modelComponent->title, $modelComponent);
            }
        }

        return $components;
    }

    /**
     * @param Collection $components
     * @return Collection
     * @throws BindingResolutionException
     */
    protected function buildSharedComponents(Collection $components): Collection
    {
        foreach (static::SHARED_COMPONENT_BUILDERS as $sharedComponentBuilderClass) {
            $sharedComponentBuilder = app()->make($sharedComponentBuilderClass);

            $sharedComponent = $sharedComponentBuilder->build();

            $components->put($sharedComponent->title, $sharedComponent);
        }

        return $components;
    }
}
