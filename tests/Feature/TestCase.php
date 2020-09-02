<?php

namespace Orion\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function withAuth($user = null, $driver = 'api')
    {
        return $this->actingAs($user ?? factory(User::class)->create(), $driver);
    }

    protected function authorize(string $ability)
    {
        Gate::define($ability, function () {
            return true;
        });

        return $this;
    }

    protected function requireAuthorization()
    {
        app()->bind('orion.authorizationRequired', function () {
            return true;
        });

        return $this;
    }

    protected function bypassAuthorization()
    {
        app()->bind('orion.authorizationRequired', function () {
            return false;
        });

        return $this;
    }

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
    protected function assertResourceListed($response, $resources, $currentPage = 1, $from = 1, $lastPage = 1, $perPage = 15, $to = null, $total = null)
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
    protected function assertResourceShown($response, $resource, $data = [])
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param string $table
     * @param array $databaseData
     */
    protected function assertResourceStored($response, $table, $databaseData, $responseData)
    {
        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => $responseData]);
        $this->assertDatabaseHas($table, $databaseData);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $originalResource
     * @param array $originalDatabaseData
     */
    protected function assertResourceUpdated($response, $table, $originalDatabaseData, $updatedDatabaseData, $responseData)
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
    protected function assertResourceDeleted($response, $resource, $data = [])
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
        $this->assertDatabaseMissing($resource->getTable(), [$resource->getKeyName() => $resource->getKey()]);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $resource
     */
    protected function assertResourceTrashed($response, $resource, $data = [])
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
        $this->assertDatabaseHas($resource->getTable(), [$resource->getKeyName() => $resource->getKey()]);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $resource
     */
    protected function assertResourceRestored($response, $resource, $data = [])
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $response->assertJson(['data' => array_merge($resource->toArray(), $data)]);
        $this->assertDatabaseHas($resource->getTable(), [$resource->getDeletedAtColumn() => null]);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     */
    protected function assertUnauthorizedResponse($response)
    {
        $response->assertStatus(403);
        $response->assertJson(['message' => 'This action is unauthorized.']);
    }
}
