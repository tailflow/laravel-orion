<?php

namespace Orion\Tests\Fixtures\App\Http\Resources;

use Orion\Http\Resources\Resource;

class TagMetaResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->toArrayWithMerge($request, ['test-field-from-resource' => 'test-value']);
    }
}
