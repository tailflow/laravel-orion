<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\User;

class HandlesStandardStoreOperationsTest extends TestCase
{
    /** @test */
    public function can_store_a_single_resource()
    {
        $payload = ['name' => 'test tag'];

        $response = $this->post('/api/tags', $payload);

        $this->assertResourceStored($response, 'tags', $payload);
        $tag = Tag::query()->first();
        $response->assertJson(['data' => $tag->toArray()]);
    }

    /** @test */
    public function can_store_a_single_resource_with_only_fillable_properties()
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body', 'tracking_id' => 'test tracking id'];

        $response = $this->actingAs(factory(User::class)->create(), 'api')->post('/api/posts', $payload);

        $this->assertResourceStored($response, 'posts', ['title' => 'test post title', 'body' => 'test post body']);
        $this->assertDatabaseMissing('posts', ['tracking_id' => 'test tracking id']);
        $response->assertJsonMissing(['tracking_id' => 'test tracking id']);
    }

    /** @test */
    public function cannot_store_a_single_resource_if_validation_fails()
    {
        $payload = ['description' => 'test tag description'];

        $response = $this->post('/api/tags', $payload, ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['name']]);
        $this->assertDatabaseMissing('tags', ['description' => 'test tag description']);
    }

    /** @test */
    public function can_store_a_single_resource_if_disable_authorization_trait_is_applied()
    {
        $payload = ['name' => 'test tag'];

        $response = $this->post('/api/tags', $payload);

        $this->assertResourceStored($response, 'tags', $payload);
    }

    /** @test */
    public function cannot_store_a_single_resource_if_no_policy_is_defined_and_disable_authorizatin_trait_is_not_applied()
    {
        $supplier = factory(Supplier::class)->create();
        $payload = ['code' => 'test history', 'supplier_id' => $supplier->id];

        $response = $this->post('/api/history', $payload, ['Accept' => 'application/json']);

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function can_store_a_single_resource_when_allowed_by_a_policy()
    {
        $payload = ['title' => 'test post title', 'body' => 'test post body'];

        $response = $this->actingAs(factory(User::class)->create(), 'api')->post('/api/posts', $payload);

        $this->assertResourceStored($response, 'posts', $payload);
    }

    /** @test */
    public function can_store_a_single_resource_transformed_by_resource()
    {
        $payload = ['key' => 'test key', 'tag_id' => factory(Tag::class)->create()->id];

        $response = $this->post('/api/tag_meta', $payload);

        $this->assertResourceStored($response, 'tag_metas', $payload);
        $tagMeta = TagMeta::query()->first();
        $response->assertJson(['data' => array_merge($tagMeta->toArray(), ['test-field-from-resource' => 'test-value'])]);
    }
}
