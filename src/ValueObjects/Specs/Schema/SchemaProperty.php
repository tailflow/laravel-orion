<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema;

use Illuminate\Contracts\Support\Arrayable;

class SchemaProperty implements Arrayable
{
    /** @var string */
    public $name;
    /** @var string */
    public $type;

    public function toArray(): array
    {
        return [
            'type' => $this->type,
        ];
    }
}
