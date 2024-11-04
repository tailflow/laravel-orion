<?php

namespace Orion\Contracts;

use Illuminate\Http\Request;

interface KeyResolver
{
    public function resolveStandardOperationKey(Request $request, array $args);

    public function resolveRelationOperationParentKey(Request $request, array $args);

    public function resolveRelationOperationRelatedKey(Request $request, array $args);
}
