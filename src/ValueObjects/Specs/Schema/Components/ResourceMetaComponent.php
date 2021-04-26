<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Components;

use Orion\ValueObjects\Specs\Component;

class ResourceMetaComponent extends Component
{
    public $title = 'ResourceMeta';
    public $type = 'object';
    public $properties = [
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
}
