<?php

namespace Orion\Tests\Feature\Relations\BelongsTo;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Feature\TestCase;
use Orion\Tests\Fixtures\App\Http\Requests\UserRequest;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\UserPolicy;

class BelongsToStandardUpdateOperationsTest extends TestCase
{
    /** @test */
    public function updating_a_single_relation_resource_without_authorization()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['name' => 'test user updated'];

        $response = $this->requireAuthorization()->patch("/api/posts/{$post->id}/user", $payload);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_authorized()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['name' => 'test user updated'];

        Gate::policy(User::class, UserPolicy::class);

        $response = $this->requireAuthorization()->withAuth($user)->patch("/api/posts/{$post->id}/user", $payload);

        $this->assertResourceUpdated($response,
            User::class,
            $user->toArray(),
            $payload
        );
    }

    /** @test */
    public function updating_a_single_relation_resource_with_only_fillable_properties()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['name' => 'test user updated', 'remember_token' => 'new token'];

        $response = $this->bypassAuthorization()->patch("/api/posts/{$post->id}/user", $payload);

        $this->assertResourceUpdated($response,
            User::class,
            $user->toArray(),
            ['name' => 'test user updated']
        );
        $this->assertDatabaseMissing('users', ['remember_token' => 'new token']);
        $response->assertJsonMissing(['password' => 'new password']);
    }

    /** @test */
    public function updating_a_single_relation_resource_when_validation_fails()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['email' => 'test@example.com'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveRequestClass')->once()->andReturn(UserRequest::class);

            return $componentsResolverMock;
        });

        $response = $this->patch("/api/posts/{$post->id}/user", $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['name']]);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    /** @test */
    public function transforming_a_single_updated_relation_resource()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['name' => 'test user updated'];

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        $response = $this->bypassAuthorization()->patch("/api/posts/{$post->id}/user", $payload);

        $this->assertResourceUpdated($response,
            User::class,
            $user->toArray(),
            $payload,
            ['test-field-from-resource' => 'test-value']
        );
    }

    /** @test */
    public function updating_a_single_resource_and_getting_included_relation()
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);
        $payload = ['name' => 'test user updated'];

        $response = $this->bypassAuthorization()->patch("/api/posts/{$post->id}/user?include=posts", $payload);

        $this->assertResourceUpdated($response,
            User::class,
            $user->toArray(),
            $payload,
            ['posts' => $user->fresh('posts')->posts->toArray()]
        );
    }
}