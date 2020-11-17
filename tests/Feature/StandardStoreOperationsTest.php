<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class StandardStoreOperationsTest extends TestCase
{
    /** @test */
    public function storing_a_single_resource_without_authorization(): void
    {
        $payload = ['title' => 'test post', 'body' => 'test post body'];

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->post('/api/posts', $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function storing_a_single_resource_when_authorized(): void
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body'];

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts', $payload);

        $this->assertResourceStored($response, Post::class, $payload);
    }

    /** @test */
    public function storing_a_single_resource_with_only_fillable_fields(): void
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body', 'tracking_id' => 'test tracking id'];

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts', $payload);

        $this->assertResourceStored($response,
            Post::class,
            ['title' => 'test post title', 'body' => 'test post body']
        );
        $this->assertDatabaseMissing('posts', ['tracking_id' => 'test tracking id']);
        $response->assertJsonMissing(['tracking_id' => 'test tracking id']);
    }

    /** @test */
    public function storing_a_single_resource_when_validation_fails(): void
    {
        $payload = ['body' => 'test post body'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(PostRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts', $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['title']]);
        $this->assertDatabaseMissing('posts', ['body' => 'test post body']);
    }

    /** @test */
    public function transforming_a_single_stored_resource(): void
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts', $payload);

        $this->assertResourceStored($response, Post::class, $payload, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function storing_a_single_resource_and_getting_included_relation(): void
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body'];
        $user = factory(User::class)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->withAuth($user)->post('/api/posts?include=user', $payload);

        $this->assertResourceStored($response, Post::class, $payload, ['user' => $user->fresh()->toArray()]);
    }
}
