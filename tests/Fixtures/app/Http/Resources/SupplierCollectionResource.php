<?php


namespace Orion\Tests\Fixtures\App\Http\Resources;

use Orion\Http\Resources\CollectionResource;

class SupplierCollectionResource extends CollectionResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
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
