<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\PostPolicy;

class StandardStoreOperationsTest extends TestCase
{
    /** @test */
    public function storing_a_single_resource_without_authorization()
    {
        $payload = ['title' => 'test post', 'body' => 'test post body'];

        $response = $this->requireAuthorization()->post('/api/posts', $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function storing_a_single_resource_when_authorized()
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body'];

        Gate::policy(Post::class, PostPolicy::class);

        $response = $this->requireAuthorization()->withAuth()->post('/api/posts', $payload);

        $this->assertResourceStored($response, Post::class, $payload);
    }

    /** @test */
    public function storing_a_single_resource_with_only_fillable_properties()
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body', 'tracking_id' => 'test tracking id'];

        $response = $this->post('/api/posts', $payload);

        $this->assertResourceStored($response,
            Post::class,
            ['title' => 'test post title', 'body' => 'test post body']
        );
        $this->assertDatabaseMissing('posts', ['tracking_id' => 'test tracking id']);
        $response->assertJsonMissing(['tracking_id' => 'test tracking id']);
    }

    /** @test */
    public function storing_a_single_resource_when_validation_fails()
    {
        $payload = ['body' => 'test post body'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(PostRequest::class);

            return $componentsResolverMock;
        });

        $response = $this->post('/api/posts', $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['title']]);
        $this->assertDatabaseMissing('posts', ['body' => 'test post body']);
    }

    /** @test */
    public function transforming_a_single_stored_resource()
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->post('/api/posts', $payload);

        $this->assertResourceStored($response, Post::class, $payload, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function storing_a_single_resource_and_getting_included_relation()
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body'];
        $user = factory(User::class)->create();

        $response = $this->withAuth($user)->post('/api/posts?include=user', $payload);

        $this->assertResourceStored($response, Post::class, $payload, ['user' => $user->fresh()->toArray()]);
    }
}
