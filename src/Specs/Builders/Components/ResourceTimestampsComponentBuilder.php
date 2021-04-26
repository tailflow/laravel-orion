<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components;

use Orion\ValueObjects\Specs\Component;

class ResourceTimestampsComponentBuilder
{
    public function build(): Component
    {
        $component = new Component();
        $component->title = 'ResourceTimestamps';
        $component->type = 'object';
        $component->properties = [
            'created_at' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
            'updated_at' => [
                'type' => 'string',
                'format' => 'date-time',
            ],
        ];

        return $component;
    }
}
