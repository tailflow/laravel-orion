<?php

declare(strict_types=1);

namespace Orion\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Orion\Contracts\Specs\Formatter;
use Orion\Contracts\Specs\Parser;
use Orion\Specs\Builders\Builder;
use Orion\Specs\Formatters\JsonFormatter;
use Orion\Specs\Formatters\YamlFormatter;
use Orion\Specs\Parsers\JsonParser;
use Orion\Specs\Parsers\YamlParser;
use Storage;
use Throwable;

class BuildSpecsCommand extends Command
{
    protected $signature = 'orion:specs {--path=} {--format=json}';

    protected $description = 'Generates API specifications in the given format';

    /**
     * @param Builder $builder
     * @return int
     * @throws BindingResolutionException
     */
    public function handle(Builder $builder): int
    {
        if (!$path = $this->option('path')) {
            $path = "specs/specs.{$this->option('format')}";
        }

        $parser = $this->resolveParser($this->option('format'));
        $formatter = $this->resolveFormatter($this->option('format'));

        $existingSpecs = [];

        try {
            if (Storage::disk('local')->exists($path)) {
                $existingSpecs = $parser->parse(Storage::disk('local')->get($path));
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return -1;
        }

        $specs = array_replace_recursive(
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

        $this->info("Specifications are saved in storage/app/{$path}");

        return 0;
    }

    /**
     * @param string $format
     * @return Parser
     * @throws BindingResolutionException
     */
    protected function resolveParser(string $format): Parser
    {
        switch ($format) {
            case 'json':
                return app()->make(JsonParser::class);
            case 'yaml':
                return app()->make(YamlParser::class);
            default:
                throw new InvalidArgumentException("Unknown format provided: {$format}");
        }
    }

    /**
     * @param string $format
     * @return Formatter
     * @throws BindingResolutionException
     */
    protected function resolveFormatter(string $format): Formatter
    {
        switch ($format) {
            case 'json':
                return app()->make(JsonFormatter::class);
            case 'yaml':
                return app()->make(YamlFormatter::class);
            default:
                throw new InvalidArgumentException("Unknown format provided: {$format}");
        }
    }
}
