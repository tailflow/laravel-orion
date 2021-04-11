<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;

class Response implements Arrayable
{
    /** @var string */
    public $statusCode;
    /** @var string */
    public $description;

    public function toArray(): array
    {
        return [
            'description' => $this->description,
        ];
    }
}
