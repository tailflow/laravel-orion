<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Illuminate\Support\Facades\App;
use Orion\Drivers\Standard\ComponentsResolver;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;
use Orion\Tests\Unit\Drivers\Standard\Stubs\StubCollectionResource;
use Orion\Tests\Unit\Drivers\Standard\Stubs\StubRequest;
use Orion\Tests\Unit\Drivers\Standard\Stubs\StubResource;
use Orion\Tests\Unit\TestCase;

class ComponentsResolverTest extends TestCase
{
    /** @test */
    public function resolving_resource_specific_request_class()
    {
        $componentsResolver = new ComponentsResolver('Stub');
        $componentsResolver->setRequestClassesNamespace('Orion\\Tests\\Unit\\Drivers\\Standard\\Stubs\\');

        $this->assertEquals(StubRequest::class, $componentsResolver->resolveRequestClass());
    }

    /** @test */
    public function resolving_default_request_class()
    {
        $componentsResolver = new ComponentsResolver('');

        $this->assertEquals(Request::class, $componentsResolver->resolveRequestClass());
    }

    /** @test */
    public function resolving_resource_specific_resource_class()
    {
        $componentsResolver = new ComponentsResolver('Stub');
        $componentsResolver->setResourceClassesNamespace('Orion\\Tests\\Unit\\Drivers\\Standard\\Stubs\\');

        $this->assertEquals(StubResource::class, $componentsResolver->resolveResourceClass());
    }

    /** @test */
    public function resolving_default_resource_class()
    {
        $componentsResolver = new ComponentsResolver('');

        $this->assertEquals(Resource::class, $componentsResolver->resolveResourceClass());
    }

    /** @test */
    public function resolving_resource_specific_collection_resource_class()
    {
        $componentsResolver = new ComponentsResolver('Stub');
        $componentsResolver->setResourceClassesNamespace('Orion\\Tests\\Unit\\Drivers\\Standard\\Stubs\\');

        $this->assertEquals(StubCollectionResource::class, $componentsResolver->resolveCollectionResourceClass());
    }

    /** @test */
    public function resolving_default_collection_resource_class()
    {
        $componentsResolver = new ComponentsResolver('');

        $this->assertNull($componentsResolver->resolveCollectionResourceClass());
    }

    /** @test */
    public function binding_request_class()
    {
        $componentsResolver = new ComponentsResolver('');

        $componentsResolver->bindRequestClass(StubRequest::class);

        $this->assertInstanceOf(StubRequest::class, App::make(Request::class));
    }
}
