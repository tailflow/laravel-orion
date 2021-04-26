<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Specs\Builders;

use Orion\Specs\Builders\Builder;
use Orion\Specs\Builders\ComponentsBuilder;
use Orion\Specs\Builders\PathsBuilder;
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
        dd(app()->make(Builder::class)->build());
    }
}
