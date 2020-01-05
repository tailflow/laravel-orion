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
            'filters' => [
                ['field' => 'name', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_or_type()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $anotherMatchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'match'])->refresh();
        $anotherMatchingTag = factory(Tag::class)->create(['priority' => 2])->refresh();
        factory(Tag::class)->create(['priority' => 3])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'name', 'operator' => '=', 'value' => 'match'],
                ['type' => 'or','field' => 'priority', 'operator' => '=', 'value' => 2]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTag->toArray(), $anotherMatchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_not_equal_operator()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['priority' => 1])->refresh();
        factory(Tag::class)->create(['priority' => 2])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'priority', 'operator' => '!=', 'value' => 2]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_less_than_operator()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['priority' => 1])->refresh();
        factory(Tag::class)->create(['priority' => 2])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'priority', 'operator' => '<', 'value' => 2]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_less_than_or_equal_operator()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $anotherMatchingTag
         */
        $matchingTag = factory(Tag::class)->create(['priority' => 1])->refresh();
        $anotherMatchingTag = factory(Tag::class)->create(['priority' => 2])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'priority', 'operator' => '<=', 'value' => 2]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTag->toArray(), $anotherMatchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_more_than_operator()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['priority' => 3])->refresh();
        factory(Tag::class)->create(['priority' => 2])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'priority', 'operator' => '>', 'value' => 2]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_more_than_or_equal_operator()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $anotherMatchingTag
         */
        $matchingTag = factory(Tag::class)->create(['priority' => 3])->refresh();
        $anotherMatchingTag = factory(Tag::class)->create(['priority' => 2])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'priority', 'operator' => '>=', 'value' => 2]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTag->toArray(), $anotherMatchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_like_operator()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $anotherMatchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'match'])->refresh();
        $anotherMatchingTag = factory(Tag::class)->create(['name' => 'another match'])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'name', 'operator' => 'like', 'value' => '%match%']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTag->toArray(), $anotherMatchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_not_like_operator()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'another match'])->refresh();
        factory(Tag::class)->create(['name' => 'match'])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'name', 'operator' => 'not like', 'value' => 'match%']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_in_operator()
    {
        /**
         * @var Tag $matchingTag
         * @var Tag $anotherMatchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'match A'])->refresh();
        $anotherMatchingTag = factory(Tag::class)->create(['name' => 'match B'])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'name', 'operator' => 'in', 'value' => ['match A', 'match B']]
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 2, 2);

        $response->assertJson([
            'data' => [$matchingTag->toArray(), $anotherMatchingTag->toArray()]
        ]);
    }

    /** @test */
    public function can_get_a_list_of_filtered_by_model_field_resources_using_not_in_operator()
    {
        /**
         * @var Tag $matchingTag
         */
        $matchingTag = factory(Tag::class)->create(['name' => 'match A'])->refresh();
        factory(Tag::class)->create(['name' => 'match B'])->refresh();

        $response = $this->post('/api/tags/search', [
            'filters' => [
                ['field' => 'name', 'operator' => 'not in', 'value' => ['match B']]
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
            'filters' => [
                ['field' => 'meta.key', 'operator' => '=', 'value' => 'match']
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
            'filters' => [
                ['field' => 'description', 'operator' => '=', 'value' => 'match']
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
            'filters' => [
                ['field' => 'name', 'operator' => '=', 'value' => 'match']
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
            'filters' => [
                ['field' => 'team.name', 'operator' => '=', 'value' => 'match']
            ]
        ]);

        $this->assertResourceListed($response, 1, 1, 1, 15, 1, 1);

        $response->assertJson([
            'data' => [$matchingSupplierA->toArray()]
        ]);
    }
}
