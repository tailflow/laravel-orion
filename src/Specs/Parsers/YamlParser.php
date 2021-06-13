<?php

declare(strict_types=1);

namespace Orion\Specs\Parsers;

use Orion\Contracts\Specs\Parser;
use Symfony\Component\Yaml\Yaml;

class YamlParser implements Parser
{
    public function parse(string $specs): array
    {
        return Yaml::parse($specs);
    }
}
