<?php

declare(strict_types=1);

namespace Orion\Specs;

use Orion\ValueObjects\RegisteredResource;

class ResourcesCacheStore
{
    /** @var RegisteredResource[] */
    protected $resources = [];

    public function addResource(RegisteredResource $resource): self
    {
        $this->resources[] = $resource;

        return $this;
    }

    public function getResources(): array
    {
        return $this->resources;
    }
}
