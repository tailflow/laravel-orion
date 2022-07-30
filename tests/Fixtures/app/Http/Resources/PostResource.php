<?php

namespace Orion\Tests\Fixtures\App\Http\Resources;

use Orion\Http\Resources\Resource;

class SampleResource extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            // Schema Properties
            'title' => $this->title,
            'body' => $this->body,
            'tracking_id' => $this->tracking_id,
            'meta' => $this->meta,
            'options' => $this->options,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,

            // Testing
            'tracking_hash' => md5($this->tracking_id), // Computed property
            // 'position' => $this->position, // Ommited property
            'user' => $this->whenLoaded('user', $this->user), // Added relation
            'category' => $this->whenLoaded('user', $this->category),  // Added relation
            $this->mergeWhen(false, ['not_merged' => 0]), // MissingValue
            $this->mergeWhen(true, ['merged' => 0]), // Merged

            // Timestamp
            'publish_at' => $this->publish_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
