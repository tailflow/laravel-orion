<?php

namespace Orion\Tests\Feature;

use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Requests\PostRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;

class StandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function updating_a_single_resource_without_authorization()
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated'];

        $response = $this->requireAuthorization()->patch("/api/posts/{$post->id}", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function updating_a_single_resource_when_authorized()
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated'];

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response,
            'posts',
            $post->toArray(),
            $payload,
            $payload
        );
    }

    /** @test */
    public function updating_a_single_resource_with_only_fillable_properties()
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated', 'tracking_id' => 'test tracking id'];

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response,
            'posts',
            $post->toArray(),
            ['title' => 'test post title updated'],
            ['title' => 'test post title updated']
        );
        $this->assertDatabaseMissing('posts', ['tracking_id' => 'test tracking id']);
        $response->assertJsonMissing(['tracking_id' => 'test tracking id']);
    }

    /** @test */
    public function updating_a_single_resource_when_validation_fails()
    {
        $post = factory(Post::class)->create();
        $payload = ['body' => 'test post body updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(PostRequest::class);

            return $componentsResolverMock;
        });

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['title']]);
        $this->assertDatabaseMissing('posts', ['body' => 'test post body updated']);
    }

    /** @test */
    public function transforming_a_single_updated_resource()
    {
        $post = factory(Post::class)->create();
        $payload = ['title' => 'test post title updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->patch("/api/posts/{$post->id}", $payload);

        $this->assertResourceUpdated($response,
            'posts',
            $post->toArray(),
            $payload,
            array_merge($payload, ['test-field-from-resource' => 'test-value'])
        );
    }

    /** @test */
    public function updating_a_single_resource_and_getting_included_relation()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['title' => 'test post title updated'];

        $response = $this->patch("/api/posts/{$post->id}?include=user", $payload);

        $this->assertResourceUpdated($response,
            'posts',
            $post->toArray(),
            $payload,
            $post->fresh('user')->toArray()
        );
    }
}
