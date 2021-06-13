<?php

declare(strict_types=1);

namespace Orion\Specs\Formatters;

use JsonException;
use Orion\Contracts\Specs\Formatter;

class JsonFormatter implements Formatter
{
    /**
     * @param array $specs
     * @return string
     * @throws JsonException
     */
    public function format(array $specs): string
    {
        return json_encode($specs, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
