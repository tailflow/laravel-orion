<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;

class StandardIndexSearchingOperationsTest extends TestCase
{
    /** @test */
    public function searching_for_resources_by_model_field()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->refresh();
        factory(Post::class)->create(['title' => 'different'])->refresh();

        $response = $this->post('/api/posts/search', [
            'search' => ['value' => 'match']
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function searching_for_resources_by_relation_field()
    {
        $matchingPostUser = factory(User::class)->create(['name' => 'match']);
        $matchingPost = factory(Post::class)->create(['user_id' => $matchingPostUser->id])->refresh();

        $nonMatchingPostUser = factory(User::class)->make(['name' => 'not match']);
        factory(Post::class)->create(['user_id' => $nonMatchingPostUser->id])->refresh();

        $response = $this->post('/api/posts/search', [
            'search' => ['value' => 'match']
        ]);

        $this->assertResourceListed($response, collect([$matchingPost]));
    }

    /** @test */
    public function searching_for_resources_with_empty_search_value()
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->refresh();
        $anotherMatchingPost = factory(Post::class)->create(['title' => 'different'])->refresh();

        $response = $this->post('/api/posts/search', [
            'search' => ['value' => '']
        ]);

        $this->assertResourceListed($response, collect([$matchingPost, $anotherMatchingPost]));
    }
}
