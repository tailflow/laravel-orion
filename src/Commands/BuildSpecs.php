<?php

declare(strict_types=1);

namespace Orion\Commands;

use File;
use Illuminate\Console\Command;
use Orion\Specs\Builders\Builder;
use Orion\Specs\Formatters\YamlFormatter;
use Orion\Specs\Parsers\YamlParser;

class BuildSpecs extends Command
{
    protected $signature = 'orion:specs {--path=}';

    protected $description = 'Generates API specifications in the given format';

    public function handle(Builder $builder, YamlParser $parser, YamlFormatter $formatter): int
    {
        if (!$path = $this->option('path')) {
            $path = base_path('specs/specs.yaml');
        }

        $existingSpecs = [];

        if (File::exists($path)) {
            $existingSpecs = $parser->parse(File::get($path));
        }

        $specs = array_merge_recursive(
            $existingSpecs,
            $builder->build()
        );

        $formattedSpecs = $formatter->format($specs);

        File::put($path, $formattedSpecs);

        return 0;
    }
}
