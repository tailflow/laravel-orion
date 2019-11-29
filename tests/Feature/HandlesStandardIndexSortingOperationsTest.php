<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;

class HandlesStandardIndexSortingOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_asc_sorted_resources_with_a_valid_sort_query_parameter()
    {
        $tagC = factory(Tag::class)->create(['name' => 'C'])->refresh();
        $tagB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $tagA = factory(Tag::class)->create(['name' => 'A'])->refresh();

        $response = $this->get('/api/tags?sort=name|asc');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 3, 3);

        $this->assertEquals($tagA->toArray(), $response->json('data.0'));
        $this->assertEquals($tagB->toArray(), $response->json('data.1'));
        $this->assertEquals($tagC->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_resources_with_sort_query_parameter_missing_direction()
    {
        $tagA = factory(Tag::class)->create(['name' => 'A'])->refresh();
        $tagB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $tagC = factory(Tag::class)->create(['name' => 'C'])->refresh();

        $response = $this->get('/api/tags?sort=name');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1 ,15, 3,3);

        $this->assertEquals($tagA->toArray(), $response->json('data.0'));
        $this->assertEquals($tagB->toArray(), $response->json('data.1'));
        $this->assertEquals($tagC->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_desc_sorted_resources_with_a_valid_sort_query_parameter()
    {
        $tagA = factory(Tag::class)->create(['name' => 'A'])->refresh();
        $tagB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $tagC = factory(Tag::class)->create(['name' => 'C'])->refresh();

        $response = $this->get('/api/tags?sort=name|desc');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 3, 3);

        $this->assertEquals($tagC->toArray(), $response->json('data.0'));
        $this->assertEquals($tagB->toArray(), $response->json('data.1'));
        $this->assertEquals($tagA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function cannot_get_a_list_of_resources_sorted_by_not_whitelisted_field()
    {
        $tagC = factory(Tag::class)->create(['description' => 'C'])->refresh();
        $tagB = factory(Tag::class)->create(['description' => 'B'])->refresh();
        $tagA = factory(Tag::class)->create(['description' => 'A'])->refresh();

        $response = $this->get('/api/tags?sort=description|asc');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 3, 3);
        $this->assertEquals($tagC->toArray(), $response->json('data.0'));
        $this->assertEquals($tagB->toArray(), $response->json('data.1'));
        $this->assertEquals($tagA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_resources_with_sort_query_parameter_mising_value()
    {
        $tagA = factory(Tag::class)->create(['name' => 'A'])->refresh();
        $tagB = factory(Tag::class)->create(['name' => 'B'])->refresh();
        $tagC = factory(Tag::class)->create(['name' => 'C'])->refresh();

        $response = $this->get('/api/tags?sort=');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 3, 3);

        $this->assertEquals($tagA->toArray(), $response->json('data.0'));
        $this->assertEquals($tagB->toArray(), $response->json('data.1'));
        $this->assertEquals($tagC->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_by_has_one_relation_field_resources()
    {
        /**
         * @var Tag $tagA
         * @var Tag $tagB
         * @var Tag $tagC
         */
        $tagA = factory(Tag::class)->create()->refresh();
        $tagA->meta()->save(factory(TagMeta::class)->make(['key' => 'A']));
        $tagB = factory(Tag::class)->create()->refresh();
        $tagB->meta()->save(factory(TagMeta::class)->make(['key' => 'B']));
        $tagC = factory(Tag::class)->create()->refresh();
        $tagC->meta()->save(factory(TagMeta::class)->make(['key' => 'C']));

        $response = $this->get('/api/tags?sort=meta~key|desc');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 3, 3);

        $this->assertEquals($tagC->toArray(), $response->json('data.0'));
        $this->assertEquals($tagB->toArray(), $response->json('data.1'));
        $this->assertEquals($tagA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_by_belongs_to_relation_field_resources()
    {
        /**
         * @var Tag $tagA
         * @var Tag $tagB
         * @var Tag $tagC
         */
        $tagA = factory(Tag::class)->create(['team_id' => factory(Team::class)->create(['name' => 'A'])->id])->refresh();
        $tagB = factory(Tag::class)->create(['team_id' => factory(Team::class)->create(['name' => 'B'])->id])->refresh();
        $tagC = factory(Tag::class)->create(['team_id' => factory(Team::class)->create(['name' => 'C'])->id])->refresh();

        $response = $this->get('/api/tags?sort=team~name|desc');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 3, 3);

        $this->assertEquals($tagC->toArray(), $response->json('data.0'));
        $this->assertEquals($tagB->toArray(), $response->json('data.1'));
        $this->assertEquals($tagA->toArray(), $response->json('data.2'));
    }

    /** @test */
    public function can_get_a_list_of_asc_sorted_by_has_one_through_relation_field_resources()
    {
        if ((float)app()->version() <= 5.7) {
            $this->markTestSkipped('hasOneThrough relation is available starting from Laravel 5.8');
        }

        /**
         * @var Team $teamA
         * @var Team $teamB
         * @var Team $teamC
         */
        $teamA = factory(Team::class)->create()->refresh();
        $supplierA = factory(Supplier::class)->create(['team_id' => $teamA->id]);
        factory(History::class)->create(['code' => 'A', 'supplier_id' => $supplierA->id]);
        $teamB = factory(Team::class)->create()->refresh();
        $supplierB = factory(Supplier::class)->create(['team_id' => $teamA->id]);
        factory(History::class)->create(['code' => 'B', 'supplier_id' => $supplierB->id]);
        $teamC = factory(Team::class)->create()->refresh();
        $supplierC = factory(Supplier::class)->create(['team_id' => $teamA->id]);
        factory(History::class)->create(['code' => 'C', 'supplier_id' => $supplierC->id]);

        $this->withoutExceptionHandling();

        $response = $this->get('/api/teams?sort=supplierHistory~code|desc');

        $this->assertSuccessfulIndexResponse($response, 1, 1, 1, 15, 3, 3);

        $this->assertEquals($teamC->toArray(), $response->json('data.0'));
        $this->assertEquals($teamB->toArray(), $response->json('data.1'));
        $this->assertEquals($teamA->toArray(), $response->json('data.2'));
    }
}
