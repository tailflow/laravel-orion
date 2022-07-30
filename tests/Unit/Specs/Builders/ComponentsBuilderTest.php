<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Specs\Builders;

use Orion\Specs\Builders\ComponentsBuilder;
use Orion\Tests\Unit\TestCase;
use Orion\Specs\ResourcesCacheStore;
use Orion\Tests\Fixtures\App\Http\Controllers\TeamsController;
use Orion\ValueObjects\RegisteredResource;

class ComponentsBuilderTest extends TestCase
{
    /**
     * @var ComponentsBuilder
     */
    protected $componentsBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $resourcesCacheStore = new ResourcesCacheStore();
        $resourcesCacheStore->addResource(
            new RegisteredResource(TeamsController::class, ['show'])
        );

        $this->componentsBuilder = new ComponentsBuilder($resourcesCacheStore);
    }

    /** @test */
    public function test_build(): void
    {
        $components = $this->componentsBuilder->build();
        $this->assertArrayHasKey('schema', $components);

        $schema = $components['schema'];
        $this->assertArrayHasKey('Team', $schema);

        $team = $schema['Team'];

        // Schema properties
        $this->assertArrayHasKey('name', $team);
        $this->assertArrayHasKey('description', $team);

        // Added properties
        $this->assertArrayHasKey('short_description', $team);
        $this->assertArrayHasKey('company', $team);
        $this->assertArrayHasKey('merged', $team);

        // Removed properties
        $this->assertArrayNotHasKey('position', $team);
        $this->assertArrayNotHasKey('not_merged', $team);
    }
}
