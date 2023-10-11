<?php

declare(strict_types=1);

namespace Orion\Tests\Unit\Drivers\Standard;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Orion\Drivers\Standard\ComponentsResolver;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Unit\Drivers\Standard\Stubs\StubCollectionResource;
use Orion\Tests\Unit\Drivers\Standard\Stubs\StubRequest;
use Orion\Tests\Unit\Drivers\Standard\Stubs\StubResource;
use Orion\Tests\Unit\TestCase;

class ComponentsResolverTest extends TestCase
{
    /** @test */
    public function resolving_resource_specific_request_class(): void
    {
        Config::set('orion.namespaces.requests', 'Orion\\Tests\\Unit\\Drivers\\Standard\\Stubs\\');

        $componentsResolver = new ComponentsResolver('Stub');

        $this->assertEquals(StubRequest::class, $componentsResolver->resolveRequestClass());
    }

    /** @test */
    public function resolving_default_request_class(): void
    {
        $componentsResolver = new ComponentsResolver('');

        $this->assertEquals(Request::class, $componentsResolver->resolveRequestClass());
    }

    /** @test */
    public function resolving_resource_specific_resource_class(): void
    {
        Config::set('orion.namespaces.resources', 'Orion\\Tests\\Unit\\Drivers\\Standard\\Stubs\\');

        $componentsResolver = new ComponentsResolver('Stub');

        $this->assertEquals(StubResource::class, $componentsResolver->resolveResourceClass());
    }

    /** @test */
    public function resolving_default_resource_class(): void
    {
        $componentsResolver = new ComponentsResolver('');

        $this->assertEquals(Resource::class, $componentsResolver->resolveResourceClass());
    }

    /** @test */
    public function resolving_resource_specific_collection_resource_class(): void
    {
        Config::set('orion.namespaces.resources', 'Orion\\Tests\\Unit\\Drivers\\Standard\\Stubs\\');

        $componentsResolver = new ComponentsResolver('Stub');

        $this->assertEquals(StubCollectionResource::class, $componentsResolver->resolveCollectionResourceClass());
    }

    /** @test */
    public function resolving_default_collection_resource_class(): void
    {
        $componentsResolver = new ComponentsResolver('');

        $this->assertNull($componentsResolver->resolveCollectionResourceClass());
    }

    /** @test */
    public function binding_request_class(): void
    {
        $componentsResolver = new ComponentsResolver('');

        $componentsResolver->bindRequestClass(StubRequest::class);

        $this->assertInstanceOf(StubRequest::class, App::make(Request::class));
    }

    /** @test */
    public function binding_policy_class(): void
    {
        $componentsResolver = new ComponentsResolver(Post::class);

        $componentsResolver->bindPolicyClass(GreenPolicy::class);

        $this->assertInstanceOf(GreenPolicy::class, Gate::getPolicyFor(Post::class));
    }
}
