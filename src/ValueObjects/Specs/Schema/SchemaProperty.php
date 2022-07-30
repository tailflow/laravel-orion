<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Schema;

use Illuminate\Contracts\Support\Arrayable;
use stdClass;

class SchemaProperty implements Arrayable
{
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var bool */
    public $nullable = false;

    public function toArray(): array | stdClass
    {
        $descriptor = [
            'type' => $this->type,
        ];

        if ($this->nullable) {
            $descriptor['nullable'] = true;
        }

        return $descriptor;
    }
}
