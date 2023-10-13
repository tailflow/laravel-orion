<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Orion\Specs\ResourcesCacheStore;

class TagsBuilder
{
    /**
     * @var ResourcesCacheStore
     */
    protected $resourcesCacheStore;

    public function __construct(ResourcesCacheStore $resourcesCacheStore)
    {
        $this->resourcesCacheStore = $resourcesCacheStore;
    }

    public function build(): array
    {
        if (!config('orion.specs.tags.generate')) {
            return [];
        }

        $resources = $this->resourcesCacheStore->getResources();
        $tags = collect(config('orion.specs.tags.default'));

        foreach ($resources as $resource) {
            if (!$tags->contains('name', $resource->tag)) $tags[] = [
                'name'        => $resource->tag,
                'description' => "API documentation for {$resource->tag}",
            ];
        }

        return $tags->toArray();
    }
}
