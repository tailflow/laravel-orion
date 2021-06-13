<?php

declare(strict_types=1);

namespace Orion\Contracts\Specs;

interface Formatter
{
    public function format(array $specs): string;
}
