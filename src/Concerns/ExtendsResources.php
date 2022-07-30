<?php


namespace Orion\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\MergeValue;

trait ExtendsResources
{
    public static $mergeAll = false;

    /**
     * Override when to force merge when mergeAll is enabled
     */
    protected function when($condition, $value, $default = null)
    {
        return static::$mergeAll ? value($value) : parent::when($condition, $value, $default);
    }

    /**
     * Override mergeWhen to force merge when mergeAll is enabled
     */
    protected function mergeWhen($condition, $value)
    {
        return static::$mergeAll ? new MergeValue(value($value)) : parent::mergeWhen($condition, $value);
    }

    /**
     * Override mergeWhen to force merge when mergeAll is enabled
     */
    public function toArrayWithMerge(Request $request, array $mergeData): array
    {
        return array_merge(parent::toArray($request), $mergeData);
    }
}
