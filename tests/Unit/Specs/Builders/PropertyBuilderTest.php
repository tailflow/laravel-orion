<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Specs\Builders;

use Orion\Specs\Builders\PathsBuilder;
use Orion\Specs\Builders\PropertyBuilder;
use Orion\Tests\Unit\TestCase;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class PropertyBuilderTest extends TestCase
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
    public function building_property(): void
    {
        $column = [
            'name' => 'example_column',
            'nullable' => true,
        ];
        $concretePropertyClass = 'Orion\ValueObjects\Specs\Schema\SchemaProperty';

        $propertyBuilder = new PropertyBuilder();
        $property = $propertyBuilder->build($column, $concretePropertyClass);

        $this->assertInstanceOf(SchemaProperty::class, $property);
        $this->assertEquals($column['name'], $property->name);
        $this->assertEquals($column['nullable'], $property->nullable);
    }
}


