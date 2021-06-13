<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;

abstract class Response implements Arrayable
{
    /** @var int */
    public $statusCode = 200;
    /** @var string */
    public $description = 'OK';

    public function toArray(): array
    {
        return [
            'description' => $this->description,
        ];
    }
}
