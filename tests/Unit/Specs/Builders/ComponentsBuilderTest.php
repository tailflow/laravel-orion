<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Specs\Builders;

use Orion\Specs\Builders\ComponentsBuilder;
use Orion\Tests\Unit\TestCase;
use Orion\Specs\ResourcesCacheStore;
use Orion\Tests\Fixtures\App\Http\Controllers\PostsController;
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
            new RegisteredResource(PostsController::class, ['show'])
        );

        $this->componentsBuilder = new ComponentsBuilder($resourcesCacheStore);
    }

    /** @test */
    public function test(): void
    {
        $components = $this->componentsBuilder->build();
        $this->assertArrayHasKey('schema', $components);

        $schema = $components['schema'];
        $this->assertArrayHasKey('Post', $schema);
    }
}
