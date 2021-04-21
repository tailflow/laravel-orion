<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;

class Component implements Arrayable
{
    /** @var string */
    public $type;

    /** @var string */
    public $title;

    /** @var array */
    public $properties;

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type,
            'properties' => $this->properties,
        ];
    }
}
