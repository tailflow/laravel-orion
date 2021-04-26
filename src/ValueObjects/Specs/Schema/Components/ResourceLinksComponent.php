<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Components;

use Orion\ValueObjects\Specs\Component;

class ResourceLinksComponent extends Component
{
    public $title = 'ResourceLinks';
    public $type = 'object';
    public $properties = [
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
}
