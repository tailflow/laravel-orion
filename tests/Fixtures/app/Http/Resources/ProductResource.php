<?php

namespace Orion\Tests\Fixtures\App\Http\Resources;

use Orion\Http\Resources\Resource;

class ProductResource extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'title' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'company_id' => $this->company_id,

            'updated_at' => $this->updated_at, // Ommited schema property
            'created_at' => $this->created_at,

            // Test added properties
            'short_description' => substr($this->description, 90), // Computed property
            'company' => $this->whenLoaded('company', $this->company), // Added relation
            $this->mergeWhen(true, ['merge_true' => 0]), // Merged
            $this->mergeWhen(false, ['merge_false' => 0]), // MissingValue

            // Test removed properties
            // 'total_revenue' => $this->updated_at, // Ommited schema property
        ];
    }
}
