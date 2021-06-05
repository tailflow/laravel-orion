<?php

declare(strict_types=1);

namespace Orion\Specs\Formatters;

use Orion\Contracts\Specs\Formatter;
use Symfony\Component\Yaml\Yaml;

class YamlFormatter implements Formatter
{
    public function format(array $specs): string
    {
        return Yaml::dump($specs, 14);
    }
}
