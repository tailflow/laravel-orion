<?php

namespace Orion\Tests\Fixtures\App\Drivers;

use Illuminate\Http\Request;
use Orion\Contracts\KeyResolver;

class TwoRouteParameterKeyResolver implements KeyResolver
{
    public function resolveStandardOperationKey(Request $request, array $args)
    {
        return $args[1];
    }

    public function resolveRelationOperationParentKey(Request $request, array $args)
    {
        return $args[1];
    }

    public function resolveRelationOperationRelatedKey(Request $request, array $args)
    {
        return $args[2] ?? null;
    }
}
