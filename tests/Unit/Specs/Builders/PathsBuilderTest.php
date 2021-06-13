<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Specs\Builders;

use Orion\Specs\Builders\Builder;
use Orion\Specs\Builders\PathsBuilder;
use Orion\Specs\Formatters\YamlFormatter;
use Orion\Tests\Unit\TestCase;

class PathsBuilderTest extends TestCase
{
    /**
     * @var PathsBuilder
     */
    protected $pathsBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pathsBuilder = app()->make(PathsBuilder::class);
    }

    /** @test */
    public function building_paths(): void
    {
//        $specs = app()->make(Builder::class)->build();
//        dd(app()->make(YamlWriter::class)->format($specs));
    }
}
