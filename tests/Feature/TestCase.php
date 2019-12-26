<?php

namespace Orion\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestResponse;
use Orion\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param TestResponse $response
     * @param int $currentPage
     * @param int $from
     * @param int $lastPage
     * @param int $perPage
     * @param int $to
     * @param int $total
     */
    protected function assertSuccessfulIndexResponse($response, $currentPage = 1, $from = 1, $lastPage = 1, $perPage = 15, $to = 3, $total = 3)
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total']
        ]);
        $response->assertJson([
            'meta' => [
                'current_page' => $currentPage,
                'from' => $from,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => $to,
                'total' => $total
            ]
        ]);
    }

    /**
     * @param TestResponse $response
     */
    protected function assertSuccessfulShowResponse($response)
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * @param TestResponse $response
     * @param string $table
     * @param array $payload
     */
    protected function assertSuccessfulStoreResponse($response, $table, $payload)
    {
        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);
        $this->assertDatabaseHas($table, $payload);
    }

    /**
     * @param TestResponse $response
     * @param Model $originalResource
     * @param array $updates
     */
    protected function assertSuccessfulUpdateResponse($response, $originalResource, $updates)
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertDatabaseHas($originalResource->getTable(), $updates);
    }

    /**
     * @param TestResponse $response
     * @param Model $resource
     * @param bool $trashed
     */
    protected function assertSuccessfulDeleteResponse($response, $resource, $trashed = false)
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        if ($trashed) {
            $this->assertDatabaseHas($resource->getTable(), [$resource->getKeyName() => $resource->getKey()]);
        } else {
            $this->assertDatabaseMissing($resource->getTable(), [$resource->getKeyName() => $resource->getKey()]);
        }
    }

    /**
     * @param TestResponse $response
     */
    protected function assertUnauthorizedResponse($response)
    {
        $response->assertStatus(403);
        $response->assertJson(['message' => 'This action is unauthorized.']);
    }
}
