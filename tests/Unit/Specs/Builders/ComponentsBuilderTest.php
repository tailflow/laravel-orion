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
        $components = $this->componentsBuilder->build();
        $this->assertArrayHasKey('schemas', $components);

        $schemas = $components['schemas'];
        $this->assertArrayHasKey('Product', $schemas);

        $product = $schemas['Product'];

        // Schema properties
        $this->assertArrayHasKey('title', $product);
        $this->assertArrayHasKey('description', $product);

        // Added properties
        $this->assertArrayHasKey('short_description', $product);
        $this->assertArrayHasKey('company', $product);
        // $this->assertArrayHasKey('merged', $product);

        // Removed properties
        $this->assertArrayNotHasKey('total_revenue', $product);
        $this->assertArrayNotHasKey('not_merged', $product);
    }
}
