<?php

namespace Orion\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexScopingOperationsTest extends TestCase
{
    /** @test */
    public function getting_a_list_of_scoped_resources_without_parameters(): void
    {
        $matchingPost = factory(Post::class)->create(['publish_at' => Carbon::now()->subHours(3)])->fresh();
        factory(Post::class)->create(['publish_at' => null])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'scopes' => [
                ['name' => 'published']
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_scoped_resources_with_parameters(): void
    {
        $matchingPost = factory(Post::class)->create(['publish_at' => Carbon::parse('2019-01-10 09:35:21')])->fresh();
        factory(Post::class)->create(['publish_at' => null])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'scopes' => [
                ['name' => 'publishedAt', 'parameters' => ['2019-01-10 09:35:21']]
            ]
        ]);

        $this->assertResourcesPaginated(
            $response,
            $this->makePaginator([$matchingPost], 'posts/search')
        );
    }

    /** @test */
    public function getting_a_list_of_scoped_resources_if_scope_is_not_whitelisted(): void
    {
        factory(Post::class)->times(5)->create();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search', [
            'scopes' => [
                ['name' => 'withMeta']
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['scopes.0.name']]);
    }
}
