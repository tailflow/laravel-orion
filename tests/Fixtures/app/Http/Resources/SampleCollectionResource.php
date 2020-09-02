<?php

namespace Orion\Tests\Fixtures\App\Http\Resources;

use Illuminate\Http\Request;
use Orion\Http\Resources\CollectionResource;

class SampleCollectionResource extends CollectionResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'test-field-from-resource' => 'test-value'
        ];
    }
}
