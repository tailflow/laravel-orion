<?php


namespace Laralord\Orion\Traits;

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
    protected function toArrayWithMerge($request, $mergeData)
    {
        return array_merge(parent::toArray($request), $mergeData);
    }
}
