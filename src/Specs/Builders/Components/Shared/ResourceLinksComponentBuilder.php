<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Shared;

use Orion\Specs\Builders\Components\SharedComponentBuilder;
use Orion\ValueObjects\Specs\Component;

class ResourceLinksComponentBuilder extends SharedComponentBuilder
{
    public function build(): Component
    {
        $component = new Component();
        $component->title = 'ResourceLinks';
        $component->type = 'object';
        $component->properties = [
            'first' => [
                'type' => 'string',
                'format' => 'uri',
            ],
            'last' => [
                'type' => 'string',
                'format' => 'uri',
            ],
            'prev' => [
                'type' => 'string',
                'format' => 'uri',
            ],
            'next' => [
                'type' => 'string',
                'format' => 'uri',
            ],
        ];

        return $component;
    }
}
