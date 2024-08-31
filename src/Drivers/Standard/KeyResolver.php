<?php

namespace Orion\Drivers\Standard;

use Illuminate\Http\Request;

class KeyResolver implements \Orion\Contracts\KeyResolver
{
    public function resolveStandardOperationKey(Request $request, array $args)
    {
        return $args[0];
    }

    public function resolveRelationOperationParentKey(Request $request, array $args)
    {
        return $args[0];
    }

    public function resolveRelationOperationRelatedKey(Request $request, array $args)
    {
        return $args[1];
    }
}
