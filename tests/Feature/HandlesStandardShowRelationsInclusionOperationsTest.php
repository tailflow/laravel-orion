<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;

class HandlesStandardShowRelationsInclusionOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_single_resource_with_included_relation()
    {
        $tag = factory(Tag::class)->create();
        $tag->team()->associate(factory(Team::class)->create());
        $tag->save();

        $response = $this->get("/api/tags/{$tag->id}?include=team");

        $this->assertResourceShown($response);
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function can_get_a_single_resource_with_multiple_included_relations()
    {
        $tag = factory(Tag::class)->create(['team_id' => factory(Team::class)->create()->id]);
        $tag->posts()->saveMany(factory(Post::class)->times(5)->make());

        $response = $this->get("/api/tags/{$tag->id}?include=team,posts");

        $this->assertResourceShown($response);
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function cannot_get_included_relation_in_a_single_resource_if_not_whitelisted()
    {
        $tag = factory(Tag::class)->create();
        $tag->meta()->save(factory(TagMeta::class)->make());
        $tag->load('meta');

        $response = $this->get("/api/tags/{$tag->id}?include=meta");

        $this->assertResourceShown($response);
        $response->assertJson(['data' => $tag->unsetRelation('meta')->toArray()]);
    }

    /** @test */
    public function cat_get_a_single_resource_with_always_included_relations()
    {
        $supplier = factory(Supplier::class)->create();
        $supplier->team()->associate(factory(Team::class)->create());
        $supplier->save();

        $response = $this->get("/api/suppliers/{$supplier->id}");

        $this->assertResourceShown($response);
        $response->assertJson(['data' => $supplier->toArray()]);
    }
}
