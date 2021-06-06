<?php

declare(strict_types=1);

namespace Orion\Specs\Parsers;

use JsonException;
use Orion\Contracts\Specs\Parser;

class JsonParser implements Parser
{
    /**
     * @param string $specs
     * @return array
     * @throws JsonException
     */
    public function parse(string $specs): array
    {
        return json_decode($specs, true, 512, JSON_THROW_ON_ERROR);
    }
}
