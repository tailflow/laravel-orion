<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;

class HandlesStandardIndexRelationsInclusionOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_resources_with_included_relation()
    {
        $tags = factory(Tag::class)->times(5)->create()->each(function($tag) {
            /**
             * @var Tag $tag
             */
            $tag->team()->associate(factory(Team::class)->create());
            $tag->save();
        });

        $response = $this->get('/api/tags?include=team');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function can_get_a_list_of_resources_with_multiple_included_relations()
    {
        $tags = factory(Tag::class)->times(5)->create()->each(function($tag) {
            /**
             * @var Tag $tag
             */
            $tag->team()->associate(factory(Team::class)->create());
            $tag->save();

            $tag->posts()->saveMany(factory(Post::class)->times(5)->make());
        });

        $response = $this->get('/api/tags?include=team,posts');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function cannot_get_included_relation_in_a_list_of_resources_if_not_whitelisted()
    {
        $tags = factory(Tag::class)->times(5)->create()->each(function($tag) {
            /**
             * @var Tag $tag
             */
            $tag->meta()->save(factory(TagMeta::class)->make());
            $tag->load('meta');
        });

        $response = $this->get('/api/tags?include=meta');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $tags->map(function ($tag) {
                /**
                 * @var Tag $tag
                 */
                return $tag->unsetRelation('meta')->toArray();
            })->toArray()
        ]);
    }

    /** @test */
    public function cat_get_a_list_of_resources_with_always_included_relations()
    {
        $suppliers = factory(Supplier::class)->times(5)->create()->each(function($supplier) {
           /**
            * @var Supplier $supplier
            */
           $supplier->team()->associate(factory(Team::class)->create());
           $supplier->save();
        });

        $response = $this->get('/api/suppliers');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 5, 5);
        $response->assertJson([
            'data' => $suppliers->map(function ($supplier) {
                /**
                 * @var Supplier $supplier
                 */
                return $supplier->toArray();
            })->toArray()
        ]);
    }
}
