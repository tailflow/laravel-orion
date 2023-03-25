<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class Path implements Arrayable
{
    /** @var string */
    public $path;

    /** @var Operation[]|Collection */
    public $operations;

    /**
     * Path constructor.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->operations = collect([]);
    }

    public function toArray(): array
    {
        return $this->operations->toArray();
    }
}
