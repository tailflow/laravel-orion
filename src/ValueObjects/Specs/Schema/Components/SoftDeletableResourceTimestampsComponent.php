<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema\Components;

class SoftDeletableResourceTimestampsComponent
{
    public $title = 'SoftDeletableResourceTimestamps';
    public $properties = [
        'created_at' => [
            'type' => 'string',
            'format' => 'date-time',
        ],
        'updated_at' => [
            'type' => 'string',
            'format' => 'date-time',
        ],
        'deleted_at' => [
            'type' => 'string',
            'format' => 'date-time',
        ],
    ];
}
