<?php

namespace Orion\Tests\Fixtures\App\Http\Resources;

use Orion\Http\Resources\Resource;

class TeamResource extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'active' => $this->active,
            'company_id' => $this->category_id,
            'created_at' => $this->created_at,

            // Test added properties
            'short_description' => substr($this->description, 90), // Computed property
            'company' => $this->whenLoaded('company', $this->company), // Added relation
            $this->mergeWhen(true, ['merged' => 0]), // Merged

            // Test removed properties
            // 'updated_at' => $this->updated_at, // Ommited schema property
            $this->mergeWhen(false, ['not_merged' => 0]), // MissingValue
        ];
    }
}
