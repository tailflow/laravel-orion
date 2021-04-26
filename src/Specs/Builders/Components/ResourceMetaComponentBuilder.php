<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components;

use Orion\ValueObjects\Specs\Component;

class ResourceMetaComponentBuilder
{
    public function build(): Component
    {
        $component = new Component();
        $component->title = 'ResourceMeta';
        $component->type = 'object';
        $component->properties = [
            'current_page' => [
                'type' => 'integer',
            ],
            'from' => [
                'type' => 'integer',
            ],
            'last_page' => [
                'type' => 'integer',
            ],
            'path' => [
                'type' => 'string',
            ],
            'per_page' => [
                'type' => 'integer',
            ],
            'to' => [
                'type' => 'integer',
            ],
            'total' => [
                'type' => 'integer',
            ],
        ];

        return $component;
    }
}
