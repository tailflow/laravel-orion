<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;

class Operation implements Arrayable
{
    /** @var string */
    public $path;

    public function toArray(): array
    {
        return [

        ];
    }
}
