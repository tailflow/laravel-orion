<?php

declare(strict_types=1);

namespace Orion\Contracts\Specs;

interface Parser
{
    public function parse(string $specs): array;
}
