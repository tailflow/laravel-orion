<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexSearchingOperationsTest extends TestCase
{
    /** @test */
    public function searching_for_resources_by_model_field(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        factory(Post::class)->create(['title' => 'different'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'search' => ['value' => 'match']
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function searching_for_resources_by_relation_field(): void
    {
        $matchingPostUser = factory(User::class)->create(['name' => 'match']);
        $matchingPost = factory(Post::class)->create(['user_id' => $matchingPostUser->id])->fresh();

        $nonMatchingPostUser = factory(User::class)->make(['name' => 'not match']);
        factory(Post::class)->create(['user_id' => $nonMatchingPostUser->id])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'search' => ['value' => 'match']
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function searching_for_resources_with_empty_search_value(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        $anotherMatchingPost = factory(Post::class)->create(['title' => 'different'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'search' => ['value' => '']
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost, $anotherMatchingPost], 'posts/search')
        );
    }
}
