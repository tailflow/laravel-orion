<?php

namespace Orion\Tests\Fixtures\App\Http\Resources;

use Orion\Http\Resources\Resource;

class SampleResource extends Resource
{
    public function toArray($request)
    {
        return $this->toArrayWithMerge($request, [
           'test-field-from-resource' => 'test-value'
        ]);
    }
}
