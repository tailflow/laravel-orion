<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StandardIndexBeforeFilterAppliedOperationsTest extends TestCase
{
    /** @test */
    public function filters_rewritten_in_before_filter_applied_take_effect_for_json_requests(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match', 'body' => 'no match'])->fresh();
        $nonMatchingPost = factory(Post::class)->create(['title' => 'no match', 'body' => 'match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->json(
            'POST',
            '/api/posts_before_filter/search',
            [
                'filters' => [
                    ['field' => 'body', 'value' => 'match'],
                ],
            ]
        );

        // The controller rewrites the "body" filter onto the "title" column, so
        // only the post whose title is "match" should be returned. Before the fix
        // the rewrite was written to the POST bag while JSON requests read filters
        // from the JSON bag, so the original "body" filter was applied instead.
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matchingPost->id);
        $response->assertJsonMissingPath('data.1');

        $this->assertNotEquals($matchingPost->id, $nonMatchingPost->id);
    }

    /** @test */
    public function filters_rewritten_in_before_filter_applied_take_effect_for_form_requests(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match', 'body' => 'no match'])->fresh();
        factory(Post::class)->create(['title' => 'no match', 'body' => 'match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post(
            '/api/posts_before_filter/search',
            [
                'filters' => [
                    ['field' => 'body', 'value' => 'match'],
                ],
            ]
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matchingPost->id);
    }

    /** @test */
    public function relation_filters_rewritten_in_before_filter_applied_take_effect_for_json_requests(): void
    {
        $user = factory(User::class)->create();
        $matchingPost = factory(Post::class)->create([
            'user_id' => $user->id, 'title' => 'match', 'body' => 'no match',
        ])->fresh();
        factory(Post::class)->create([
            'user_id' => $user->id, 'title' => 'no match', 'body' => 'match',
        ])->fresh();

        Gate::policy(User::class, GreenPolicy::class);
        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->json(
            'POST',
            "/api/users/{$user->id}/posts/search",
            [
                'filters' => [
                    ['field' => 'body', 'value' => 'match'],
                ],
            ]
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matchingPost->id);
    }
}
