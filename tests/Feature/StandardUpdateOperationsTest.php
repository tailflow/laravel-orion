<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\AccessKey;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class StandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function updating_a_single_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated'];

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function updating_a_single_resource_when_authorized(): void
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['title' => 'test post title updated'];

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response,
            Post::class,
            $post->toArray(),
            $payload
        );
    }

    /** @test */
    public function updating_a_single_resource_with_custom_key(): void
    {
        $accessKey = factory(AccessKey::class)->create();
        $payload = ['name' => 'test access key name updated'];

        Gate::policy(AccessKey::class, GreenPolicy::class);

        $response = $this->patch("/api/access_keys/{$accessKey->key}", $payload);

        $this->assertResourceUpdated($response,
            AccessKey::class,
            $accessKey->toArray(),
            $payload
        );
    }

    /** @test */
    public function updating_a_single_resource_with_only_fillable_fields(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated', 'tracking_id' => 'test tracking id'];

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response,
            Post::class,
            $post->toArray(),
            ['title' => 'test post title updated']
        );
        $this->assertDatabaseMissing('posts', ['tracking_id' => 'test tracking id']);
        $response->assertJsonMissing(['tracking_id' => 'test tracking id']);
    }

    /** @test */
    public function updating_a_single_resource_when_validation_fails(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['body' => 'test post body updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(PostRequest::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['title']]);
        $this->assertDatabaseMissing('posts', ['body' => 'test post body updated']);
    }

    /** @test */
    public function transforming_a_single_updated_resource(): void
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response,
            Post::class,
            $post->toArray(),
            $payload,
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function updating_a_single_resource_and_getting_included_relation(): void
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['title' => 'test post title updated'];

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->withAuth($user)->patch("/api/posts/{$post->id}?include=user", $payload);

        $this->assertResourceUpdated($response,
            Post::class,
            $post->toArray(),
            $payload,
            ['user' => $user->fresh()->toArray()]
        );
    }
}
