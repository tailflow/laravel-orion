<?php

namespace Orion\Concerns;

use Orion\Http\Requests\Request;

trait InteractsWithBatchResources
{
    /**
     * @return string
     */
    protected function resolveResourceKeyName(): string
    {
        $resourceModelClass = $this->resolveResourceModelClass();
        return (new $resourceModelClass)->getKeyName();
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function resolveResourceKeys(Request $request): array
    {
        $resources = $request->get('resources', []);
        if (array_keys($resources) !== range(0, count($resources) - 1)) {
            return array_keys($resources);
        }

        return array_values($resources);
    }
}