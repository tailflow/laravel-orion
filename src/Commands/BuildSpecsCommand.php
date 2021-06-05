<?php

declare(strict_types=1);

namespace Orion\Commands;

use Illuminate\Console\Command;
use Orion\Specs\Builders\Builder;
use Orion\Specs\Formatters\YamlFormatter;
use Orion\Specs\Parsers\YamlParser;
use Storage;
use Throwable;

class BuildSpecsCommand extends Command
{
    protected $signature = 'orion:specs {--path=}';

    protected $description = 'Generates API specifications in the given format';

    /**
     * @param Builder $builder
     * @param YamlParser $parser
     * @param YamlFormatter $formatter
     * @return int
     */
    public function handle(Builder $builder, YamlParser $parser, YamlFormatter $formatter): int
    {
        if (!$path = $this->option('path')) {
            $path = 'specs/specs.yaml';
        }

        $existingSpecs = [];

        try {
            if (Storage::disk('local')->exists($path)) {
                $existingSpecs = $parser->parse(Storage::disk('local')->get($path));
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return -1;
        }

        $specs = array_merge_recursive(
            $existingSpecs,
            $builder->build()
        );

        $formattedSpecs = $formatter->format($specs);

        try {
            Storage::disk('local')->put($path, $formattedSpecs);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return -1;
        }

        return 0;
    }
}
