<?php

namespace Orion\Tests\Unit\Http\Requests;

use Illuminate\Routing\Route;
use Orion\Tests\Unit\Http\Requests\Stubs\ControllerStub;
use Orion\Tests\Unit\Http\Requests\Stubs\RelationControllerStub;
use Orion\Tests\Unit\Http\Requests\Stubs\RequestStub;
use Orion\Tests\Unit\TestCase;

class RequestTest extends TestCase
{
    /** @test */
    public function resolving_store_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('POST', '/api/posts', [ControllerStub::class, 'store']);
        });

        $this->assertSame([
            'common-rules-field' => 'required',
            'store-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_update_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('PATCH', '/api/posts/1', [ControllerStub::class, 'update']);
        });

        $this->assertSame([
            'common-rules-field' => 'required',
            'update-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_associate_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('POST', '/api/posts/1/user/associate', [RelationControllerStub::class, 'associate']);
        });

        $this->assertSame([
            'related_key' => 'required',
            'associate-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_attach_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('POST', '/api/posts/1/tags/attach', [RelationControllerStub::class, 'attach']);
        });

        $this->assertSame([
            'resources' => 'present',
            'duplicates' => ['sometimes', 'boolean'],
            'attach-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_detach_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('DELETE', '/api/posts/1/tags/detach', [RelationControllerStub::class, 'detach']);
        });

        $this->assertSame([
            'resources' => 'present',
            'detach-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_sync_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('PATCH', '/api/posts/1/tags/sync', [RelationControllerStub::class, 'sync']);
        });

        $this->assertSame([
            'resources' => 'present',
            'detaching' => ['sometimes', 'boolean'],
            'sync-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_toggle_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('PATCH', '/api/posts/1/tags/toggle', [RelationControllerStub::class, 'toggle']);
        });

        $this->assertSame([
            'resources' => 'present',
            'toggle-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_update_pivot_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('PATCH', '/api/posts/1/tags/pivot', [RelationControllerStub::class, 'updatePivot']);
        });

        $this->assertSame([
            'pivot' => ['required', 'array'],
            'update-pivot-rules-field' => 'required'
        ], $stub->rules());
    }

    /** @test */
    public function resolving_custom_endpoint_rules()
    {
        $stub = new RequestStub();
        $stub->setRouteResolver(function () {
            return new Route('POST', '/api/posts/1/custom', [ControllerStub::class, 'customEndpoint']);
        });

        $this->assertSame([], $stub->rules());
    }
}
