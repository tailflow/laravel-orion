<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Components;

use Orion\ValueObjects\Specs\Component;

class ResourceTimestampsComponent extends Component
{
    public $title = 'ResourceTimestamps';
    public $type = 'object';
    public $properties = [
        'created_at' => [
            'type' => 'string',
            'format' => 'date-time',
        ],
        'updated_at' => [
            'type' => 'string',
            'format' => 'date-time',
        ],
    ];
}
