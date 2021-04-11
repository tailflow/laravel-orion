<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;

class Operation implements Arrayable
{
    /** @var string */
    public $id;
    /** @var string */
    public $method;
    /** @var string */
    public $summary;

    public function toArray(): array
    {
        return [
            'operationId' => $this->id,
            'summary' => $this->summary
        ];
    }
}
