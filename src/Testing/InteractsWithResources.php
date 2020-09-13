<?php

namespace Orion\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait InteractsWithResources
{
    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param LengthAwarePaginator $paginator
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourceListed($response, LengthAwarePaginator $paginator, array $mergeData = [], bool $exact = true): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total']
        ]);

        $expected = [
            'data' => $paginator->forPage($paginator->currentPage(), $paginator->perPage())->values()->map(function ($resource) use ($mergeData) {
                $arrayRepresentation = is_array($resource) ? $resource : $resource->fresh()->toArray();
                $arrayRepresentation = array_merge($arrayRepresentation, $mergeData);

                return $arrayRepresentation;
            })->toArray(),
            'links' => [
                'first' => $this->resolveResourceLink($paginator, 1),
                'last' => $this->resolveResourceLink($paginator, $paginator->lastPage()),
                'prev' => $paginator->currentPage() > 1 ? $this->resolveResourceLink($paginator, $paginator->currentPage() - 1) : null,
                'next' => $paginator->lastPage() > 1 ? $this->resolveResourceLink($paginator, $paginator->currentPage() + 1) : null,
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $this->resolvePath($this->resolveBasePath($paginator)),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->perPage() > $paginator->total() ? $paginator->total() : $paginator->currentPage() * $paginator->perPage(),
                'total' => $paginator->total()
            ]
        ];

        if ((float) app()->version() >= 8.0) {
            $expected['meta']['links'] = $this->buildMetaLinks($paginator);
            $meta = $expected['meta'];
            ksort($meta);
            $expected['meta'] = $meta;
        }

        $this->assertResponseContent($expected, $response, $exact);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model|array $resource
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourceShown($response, $resource, $mergeData = [], bool $exact = true): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $expected = ['data' => array_merge(is_array($resource) ? $resource : $resource->fresh()->toArray(), $mergeData)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param string $model
     * @param array $databaseData
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourceStored($response, string $model, array $databaseData, array $mergeData = [], bool $exact = true): void
    {
        $this->assertDatabaseHas((new $model)->getTable(), $databaseData);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data']);

        $resource = $model::where($databaseData)->first();
        $expected = ['data' => array_merge($resource->toArray(), $mergeData)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param string $model
     * @param array $originalDatabaseData
     * @param array $updatedDatabaseData
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourceUpdated($response, string $model, array $originalDatabaseData, array $updatedDatabaseData, array $mergeData = [], bool $exact = true): void
    {
        $table = (new $model)->getTable();
        $this->assertDatabaseMissing($table, $originalDatabaseData);
        $this->assertDatabaseHas($table, $updatedDatabaseData);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $resource = $model::where($updatedDatabaseData)->first();
        $expected = ['data' => array_merge($resource->toArray(), $mergeData)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model|array $resource
     * @param array $data
     * @param bool $exact
     */
    protected function assertResourceDeleted($response, $resource, $data = [], bool $exact = true): void
    {
        $this->assertDatabaseMissing($resource->getTable(), [$resource->getKeyName() => $resource->getKey()]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $expected = ['data' => array_merge(is_array($resource) ? $resource : $resource->toArray(), $data)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model|\Illuminate\Database\Eloquent\SoftDeletes|array $resource
     * @param array $data
     * @param bool $exact
     */
    protected function assertResourceTrashed($response, $resource, $data = [], bool $exact = true): void
    {
        $resource = $resource->fresh();
        if (!$resource) {
            $this->fail('The resource was deleted, not trashed.');
        }
        if (is_null($resource->{$resource->getDeletedAtColumn()})) {
            $this->fail('The resource was not trashed.');
        }

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $expected = ['data' => array_merge(is_array($resource) ? $resource : $resource->toArray(), $data)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model|\Illuminate\Database\Eloquent\SoftDeletes|array $resource
     * @param array $data
     * @param bool $exact
     */
    protected function assertResourceRestored($response, $resource, $data = [], bool $exact = true): void
    {
        $this->assertDatabaseHas($resource->getTable(), [$resource->getKeyName() => $resource->getKey(), $resource->getDeletedAtColumn() => null]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $expected = ['data' => array_merge(is_array($resource) ? $resource : $resource->fresh()->toArray(), $data)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    protected function assertResponseContent(array $expected, $response, bool $exact)
    {
        if ($exact) {
            $this->assertJsonSame($expected, $response);
        } else {
            $response->assertJson($expected);
        }
    }

    protected function assertJsonSame(array $expected, $response): void
    {
        $actual = json_decode($response->getContent(), true);
        $this->assertSame($expected, $actual);
    }

    protected function resolveResourceLink(LengthAwarePaginator $paginator, int $page): string
    {
        $path = $this->resolvePath($this->resolveBasePath($paginator));
        return "{$path}?page={$page}";
    }

    protected function resolveBasePath(LengthAwarePaginator $paginator): string
    {
        if ((float) app()->version() >= 6.0) {
            return $paginator->path();
        }

        $paginatorDescriptor = $paginator->toArray();
        return $paginatorDescriptor['path'];
    }

    protected function resolvePath(string $basePath): string
    {
        return config('app.url')."/api/$basePath";
    }

    protected function buildMetaLinks(LengthAwarePaginator $paginator): array
    {
        $links = [
            $this->buildMetaLink(
                $paginator->currentPage() > 1 ? $this->resolveResourceLink($paginator, $paginator->currentPage() - 1) : null,
                'Previous',
                false
            )
        ];

        for ($page = 1; $page <= $paginator->lastPage(); $page++) {
            $links[] = $this->buildMetaLink(
                $this->resolveResourceLink($paginator, $page),
                $page,
                $paginator->currentPage() === $page
            );
        }

        $links[] = $this->buildMetaLink(
            $paginator->lastPage() > 1 ? $this->resolveResourceLink($paginator, $paginator->currentPage() + 1) : null,
            'Next',
            false
        );

        return $links;
    }

    protected function buildMetaLink(?string $url, $label, bool $active): array
    {
        return [
            'url' => $url,
            'label' => $label,
            'active' => $active
        ];
    }

    /**
     * @param Collection|array $items
     * @param string $path
     * @param int $currentPage
     * @param int $perPage
     * @param int|null $total
     * @return LengthAwarePaginator
     */
    protected function makePaginator($items, string $path, int $currentPage = 1, int $perPage = 15, int $total = null): LengthAwarePaginator
    {
        if (is_array($items)) {
            $items = collect($items);
        }

        return new LengthAwarePaginator($items, $total ?? $items->count(), $perPage, $currentPage, ['path' => $path]);
    }
}