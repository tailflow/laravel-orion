<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;

class Request implements Arrayable
{
    public function toArray(): array
    {
        return [];
    }
}
