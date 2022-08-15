<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Specs\Builders;

use Orion\Specs\Builders\ComponentsBuilder;
use Orion\Tests\Unit\TestCase;
use Orion\Specs\ResourcesCacheStore;
use Orion\Tests\Fixtures\App\Http\Controllers\ProductsController;
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
            new RegisteredResource(ProductsController::class, ['show'])
        );

        $this->componentsBuilder = new ComponentsBuilder($resourcesCacheStore);
    }

    /** @test */
    public function test_build(): void
    {
        if ((float) app()->version() <= 8.0) {
            $this->markTestSkipped('Unsupported framework version');
        }

        $components = $this->componentsBuilder->build();
        $this->assertArrayHasKey('schemas', $components);

        $schemas = $components['schemas'];
        $this->assertArrayHasKey('Product', $schemas);

        $resource = $schemas['ProductResource']['properties'];

        // Schema properties
        $this->assertArrayHasKey('title', $resource);
        $this->assertArrayHasKey('description', $resource);

        // Added properties
        $this->assertArrayHasKey('short_description', $resource);
        $this->assertArrayHasKey('company', $resource);
        $this->assertArrayHasKey('merge_true', $resource);
        $this->assertArrayHasKey('merge_false', $resource);

        // Removed properties
        $this->assertArrayNotHasKey('total_revenue', $resource);
    }
}
