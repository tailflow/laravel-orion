<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;

class HandlesStandardIndexFilteringOperationsTest extends TestCase
{
    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'match'])->refresh();
        factory(Tag::class)->create(['name' => 'not match'])->refresh();

        $response = $this->post('/api/tags/search', [
            'filter' => [
                ['field' => 'name', 'operation' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_relation_field_resources()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $notMatchingTag
         */
        $matchingTag = factory(Tag::class)->create()->refresh();
        $matchingTag->meta()->save(factory(TagMeta::class)->make(['key' => 'match']));
        $notMatchingTag = factory(Tag::class)->create()->refresh();
        $notMatchingTag->meta()->save(factory(TagMeta::class)->make(['key' => 'not match']));

        $response = $this->post('/api/tags/search', [
            'filter' => [
                ['field' => 'meta.key', 'operation' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function cannot_get_a_list_of_resources_filtered_by_not_whitelisted_field()
    {
        /**
         * @var Tag $matchingTagA
         * @var Tag $notMatchingTagB
         */
        $matchingTagA = factory(Tag::class)->create(['description' => 'match'])->refresh();
        $notMatchingTagB = factory(Tag::class)->create(['description' => 'not match'])->refresh();

        $response = $this->post('/api/tags/search', [
            'filter' => [
                ['field' => 'description', 'operation' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTagA->toArray(), $notMatchingTagB->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_with_wildcard_whitelisting()
    {
        /**
         * @var Supplier $matchingSupplierA
         * @var Supplier $notMatchingSupplierB
         */
        $matchingSupplierA = factory(Supplier::class)->create(['name' => 'match'])->refresh();
        factory(Supplier::class)->create(['name' => 'not match'])->refresh();

        $response = $this->post('/api/suppliers/search', [
            'filter' => [
                ['field' => 'name', 'operation' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingSupplierA->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_relation_field_resources_with_wildcard_whitelisting()
    {
        /**
         * @var Supplier $matchingSupplierA
         * @var Supplier $notMatchingSupplierB
         */
        $matchingSupplierATeam = factory(Team::class)->create(['name' => 'match']);
        $matchingSupplierA = factory(Supplier::class)->create(['team_id' => $matchingSupplierATeam->id])->refresh();
        $notMatchingSupplierBTeam = factory(Team::class)->create(['name' => 'not match']);
        factory(Supplier::class)->create(['team_id' => $notMatchingSupplierBTeam->id])->refresh();

        $response = $this->post('/api/suppliers/search', [
            'filter' => [
                ['field' => 'team.name', 'operation' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingSupplierA->toArray()]
        ]);
    }
}
