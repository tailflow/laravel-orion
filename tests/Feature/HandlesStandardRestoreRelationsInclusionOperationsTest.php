<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;

class HandlesStandardRestoreRelationsInclusionOperationsTest extends TestCase
{
    /** @test */
    public function can_restore_single_resource_and_get_included_relation()
    {
        $trashedTagMeta = factory(TagMeta::class)->state('trashed')->create(['tag_id' => factory(Tag::class)->create()->id]);

        $response = $this->post("/api/tag_meta/{$trashedTagMeta->id}/restore?include=tag");

        $this->assertResourceRestored($response, $trashedTagMeta);
        $trashedTagMeta->load('tag');
        $response->assertJson(['data' => array_merge(['test-field-from-resource' => 'test-value'], $trashedTagMeta->fresh()->toArray())]);
    }
}
