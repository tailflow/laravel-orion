<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

class ServersBuilder
{
    public function build(): array
    {
        return config('orion.specs.servers', []);
    }
}
