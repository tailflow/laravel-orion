<?php

namespace Orion\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait InteractsWithResources
{
    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Collection|null $resources
     * @param int $currentPage
     * @param int $from
     * @param int $lastPage
     * @param int $perPage
     * @param int|null $to
     * @param int|null $total
     */
    protected function assertResourceListed($response, $resources, $currentPage = 1, $from = 1, $lastPage = 1, $perPage = 15, $to = null, $total = null): void
    {
        if (!$to) {
            $to = $resources->count();
        }

        if (!$total) {
            $total = $resources->count();
        }

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total']
        ]);
        $response->assertJson([
            'data' => $resources->map(function ($resource) {
                return is_array($resource) ? $resource : $resource->toArray();
            })->toArray()
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
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $resource
     * @param array $data
     */
    protected function assertResourceShown($response, $resource, $data = []): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param string $table
     * @param array $databaseData
     * @param array $responseData
     */
    protected function assertResourceStored($response, string $table, array $databaseData, array $responseData): void
    {
        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => $responseData]);
        $this->assertDatabaseHas($table, $databaseData);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param string $table
     * @param array $originalDatabaseData
     * @param array $updatedDatabaseData
     * @param array $responseData
     */
    protected function assertResourceUpdated($response, string $table, array $originalDatabaseData, array $updatedDatabaseData, array $responseData): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => $responseData]);
        $this->assertDatabaseMissing($table, $originalDatabaseData);
        $this->assertDatabaseHas($table, $updatedDatabaseData);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $resource
     * @param array $data
     */
    protected function assertResourceDeleted($response, $resource, $data = []): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
        $this->assertDatabaseMissing($resource->getTable(), [$resource->getKeyName() => $resource->getKey()]);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $resource
     * @param array $data
     */
    protected function assertResourceTrashed($response, $resource, $data = []): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
        $this->assertDatabaseHas($resource->getTable(), [$resource->getKeyName() => $resource->getKey()]);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model|\Illuminate\Database\Eloquent\SoftDeletes $resource
     * @param array $data
     */
    protected function assertResourceRestored($response, $resource, $data = []): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
        $this->assertDatabaseHas($resource->getTable(), [$resource->getDeletedAtColumn() => null]);
    }
}