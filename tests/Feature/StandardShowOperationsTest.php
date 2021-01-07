<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Tests\Fixtures\App\Http\Resources\SampleResource;
use Orion\Tests\Fixtures\App\Models\AccessKey;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Fixtures\App\Policies\RedPolicy;

class StandardShowOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_single_resource_without_authorization(): void
    {
        $post = factory(Post::class)->create();

        Gate::policy(Post::class, RedPolicy::class);

        $response = $this->get("/api/posts/{$post->id}");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function getting_a_single_resource_when_authorized(): void
    {
        $user = factory(User::class)->create();
        $post = factory(Post::class)->create(['user_id' => $user->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}");

        $this->assertResourceShown($response, $post);
    }

    /** @test */
    public function getting_a_single_resource_with_custom_key(): void
    {
        $accessKey = factory(AccessKey::class)->create();

        Gate::policy(AccessKey::class, GreenPolicy::class);

        $response = $this->get("/api/access_keys/{$accessKey->key}");

        $this->assertResourceShown($response, $accessKey);
    }

    /** @test */
    public function getting_a_single_trashed_resource_when_with_trashed_query_parameter_is_missing(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$trashedPost->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function getting_a_single_trashed_resource_when_with_trashed_query_parameter_is_present(): void
    {
        $trashedPost = factory(Post::class)->state('trashed')->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$trashedPost->id}?with_trashed=true");

        $this->assertResourceShown($response, $trashedPost);
    }

    /** @test */
    public function getting_a_single_transformed_resource(): void
    {
        $post = factory(Post::class)->create();

        app()->bind(ComponentsResolver::class, function () {
            $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)->makePartial();
            $componentsResolverMock->shouldReceive('resolveResourceClass')->once()->andReturn(SampleResource::class);

            return $componentsResolverMock;
        });

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}");

        $this->assertResourceShown($response, $post, ['test-field-from-resource' => 'test-value']);
    }

    /** @test */
    public function getting_a_single_resource_with_included_relation(): void
    {
        $post = factory(Post::class)->create(['user_id' => factory(User::class)->create()->id]);

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->get("/api/posts/{$post->id}?include=user");

        $this->assertResourceShown($response, $post->fresh('user')->toArray());
    }
}
