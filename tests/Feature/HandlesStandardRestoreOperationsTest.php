<?php

namespace Orion\Tests\Feature;

use Orion\Tests\Fixtures\App\Models\History;
use Orion\Tests\Fixtures\App\Models\Supplier;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\TagMeta;
use Orion\Tests\Fixtures\App\Models\Team;

class HandlesStandardRestoreOperationsTest extends TestCase
{
    /** @test */
    public function can_restore_a_trashed_resource()
    {
        $trashedTeam = factory(Team::class)->state('trashed')->create();

        $response = $this->post("/api/teams/{$trashedTeam->id}/restore");

        $this->assertResourceRestored($response, $trashedTeam);
    }

    /** @test */
    public function not_found_is_returned_if_resource_is_not_marked_as_soft_deletable()
    {
        $supplier = factory(Supplier::class)->create();

        $response = $this->post("/api/supplier/{$supplier->id}/restore");

        $response->assertNotFound();
    }

    /** @test */
    public function cannot_restore_a_single_resource_if_no_policy_is_defined_and_disable_authorizatin_trait_is_not_applied()
    {
        $supplier = factory(Supplier::class)->create();
        $trashedHistory = factory(History::class)->state('trashed')->create(['supplier_id' => $supplier->id]);

        $response = $this->post("/api/history/{$trashedHistory->id}/restore");

        $this->assertUnauthorizedResponse($response);
    }

    /** @test */
    public function can_restore_a_single_resource_transformed_by_resource()
    {
        $trashedTagMeta  = factory(TagMeta::class)->state('trashed')->create(['tag_id' => factory(Tag::class)->create()->id]);

        $response = $this->post("/api/tag_meta/{$trashedTagMeta->id}/restore");

        $this->assertResourceRestored($response, $trashedTagMeta);
        $response->assertJson(['data' => array_merge($trashedTagMeta->fresh()->toArray(), ['test-field-from-resource' => 'test-value'])]);
    }
}
