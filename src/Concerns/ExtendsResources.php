<?php


namespace Orion\Concerns;

use Illuminate\Http\Request;

trait ExtendsResources
{
    /**
     * Merges transformed resource with the given data.
     *
     * @param Request $request
     * @param array $mergeData
     * @return array
     */
    public function toArrayWithMerge(Request $request, array $mergeData) : array
    {
        return array_merge(parent::toArray($request), $mergeData);
    }
}
