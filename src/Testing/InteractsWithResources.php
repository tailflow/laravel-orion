<?php

namespace Orion\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait InteractsWithResources
{
    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param LengthAwarePaginator $paginator
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourcesPaginated($response, LengthAwarePaginator $paginator, array $mergeData = [], bool $exact = true): void
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
     * @param Collection $entities
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourcesListed($response, Collection $entities, array $mergeData = [], bool $exact = true): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
        ]);

        $expected = [
            'data' => $entities->map(function ($resource) use ($mergeData) {
                $arrayRepresentation = is_array($resource) ? $resource : $resource->fresh()->toArray();
                $arrayRepresentation = array_merge($arrayRepresentation, $mergeData);

                return $arrayRepresentation;
            })->toArray(),
        ];

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
        $databaseData = Arr::except($databaseData, 'pivot');
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
        $originalDatabaseData = Arr::except($originalDatabaseData, 'pivot');
        $updatedDatabaseData = Arr::except($updatedDatabaseData, 'pivot');

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
            self::fail('The resource was deleted, not trashed.');
        }
        if (is_null($resource->{$resource->getDeletedAtColumn()})) {
            self::fail('The resource was not trashed.');
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

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $parentModel
     * @param Model $relationModel
     * @param string $relation
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourceAssociated($response, Model $parentModel, Model $relationModel, string $relation, array $mergeData = [], bool $exact = true): void
    {
        $relationModel = $relationModel->fresh();
        $foreignKeyGetter = (float) app()->version() > 5.7 ? 'getForeignKeyName' : 'getForeignKey';
        self::assertSame((string) $parentModel->getKey(), (string) $relationModel->{$relationModel->{$relation}()->{$foreignKeyGetter}()});

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $expected = ['data' => array_merge($relationModel->toArray(), $mergeData)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    /**
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     * @param Model $relationModel
     * @param string $relation
     * @param array $mergeData
     * @param bool $exact
     */
    protected function assertResourceDissociated($response, Model $relationModel, string $relation, array $mergeData = [], bool $exact = true): void
    {
        $relationModel = $relationModel->fresh();
        $foreignKeyGetter = (float) app()->version() > 5.7 ? 'getForeignKeyName' : 'getForeignKey';
        self::assertSame(null, $relationModel->{$relationModel->{$relation}()->{$foreignKeyGetter}()});

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $expected = ['data' => array_merge($relationModel->toArray(), $mergeData)];

        $this->assertResponseContent($expected, $response, $exact);
    }

    protected function assertResourcesAttached($response, string $relation, Model $parentModel, Collection $relationModels, array $pivotFields = [], bool $exact = true): void
    {
        foreach ($relationModels as $relationModel) {
            $this->assertResourceAttached(
                $relation,
                $parentModel,
                $relationModel,
                Arr::get($pivotFields, $relationModel->getKey(), [])
            );
        }

        $this->assertResponseContent(['attached' => $relationModels->pluck('id')->toArray()], $response, $exact);
    }

    protected function assertResourcesDetached($response, string $relation, Model $parentModel, Collection $relationModels, bool $exact = true): void
    {
        foreach ($relationModels as $relationModel) {
            $this->assertResourceDetached($relation, $parentModel, $relationModel);
        }

        $this->assertResponseContent(['detached' => $relationModels->pluck('id')->toArray()], $response, $exact);
    }

    protected function assertResourcesSynced($response, string $relation, Model $parentModel, array $syncMap, array $pivotFields = [], bool $exact = true): void
    {
        foreach (array_merge($syncMap['attached'], $syncMap['updated'], $syncMap['remained']) as $relationModel) {
            $this->assertResourceAttached(
                $relation,
                $parentModel,
                $relationModel,
                Arr::get($pivotFields, $relationModel->getKey(), [])
            );
        }

        foreach ($syncMap['detached'] as $relationModel) {
            $this->assertResourceDetached($relation, $parentModel, $relationModel);
        }

        $this->assertResponseContent(
            [
                'attached' => collect($syncMap['attached'])->pluck('id')->toArray(),
                'detached' => collect($syncMap['detached'])->pluck('id')->toArray(),
                'updated' => collect($syncMap['updated'])->pluck('id')->toArray(),
            ],
            $response,
            $exact
        );
    }

    protected function assertResourcesToggled($response, string $relation, Model $parentModel, array $syncMap, array $pivotFields = [], bool $exact = true): void
    {
        foreach ($syncMap['attached'] as $relationModel) {
            $this->assertResourceAttached(
                $relation,
                $parentModel,
                $relationModel,
                Arr::get($pivotFields, $relationModel->getKey(), [])
            );
        }

        foreach ($syncMap['detached'] as $relationModel) {
            $this->assertResourceDetached($relation, $parentModel, $relationModel);
        }

        $this->assertResponseContent(
            [
                'attached' => collect($syncMap['attached'])->pluck('id')->toArray(),
                'detached' => collect($syncMap['detached'])->pluck('id')->toArray(),
            ],
            $response,
            $exact
        );
    }

    protected function assertResourceAttached(string $relation, Model $parentModel, Model $relationModel, array $pivotFields = []): void
    {
        $pivotFields = $this->castFieldsToJson($pivotFields);

        $this->assertDatabaseHas($parentModel->{$relation}()->getTable(), array_merge([
            $parentModel->{$relation}()->getForeignPivotKeyName() => $parentModel->getKey(),
            $parentModel->{$relation}()->getRelatedPivotKeyName() => $relationModel->getKey()
        ], $pivotFields));
    }

    protected function assertResourceDetached(string $relation, Model $parentModel, Model $relationModel): void
    {
        $this->assertDatabaseMissing($parentModel->{$relation}()->getTable(), [
            $parentModel->{$relation}()->getForeignPivotKeyName() => $parentModel->getKey(),
            $parentModel->{$relation}()->getRelatedPivotKeyName() => $relationModel->getKey()
        ]);
    }

    protected function assertResourcePivotUpdated($response, string $relation, Model $parentModel, Model $relationModel, array $pivotFields, bool $exact = true): void
    {
        $this->assertResponseContent(['updated' => [$relationModel->getKey()]], $response, $exact);

        $pivotFields = $this->castFieldsToJson($pivotFields);

        $this->assertDatabaseHas($parentModel->{$relation}()->getTable(), array_merge([
            $parentModel->{$relation}()->getForeignPivotKeyName() => $parentModel->getKey(),
            $parentModel->{$relation}()->getRelatedPivotKeyName() => $relationModel->getKey()
        ], $pivotFields));
    }

    protected function assertNoResourcesAttached($response, string $relation, Model $parentModel, bool $exact = true): void
    {
        self::assertSame(0, $parentModel->{$relation}()->count());

        $this->assertResponseContent(['attached' => []], $response, $exact);
    }

    protected function assertNoResourcesDetached($response, string $relation, Model $parentModel, int $expectedCount, bool $exact = true): void
    {
        self::assertSame($expectedCount, $parentModel->{$relation}()->count());

        $this->assertResponseContent(['detached' => []], $response, $exact);
    }

    protected function assertNoResourcesSynced($response, string $relation, Model $parentModel, bool $exact = true): void
    {
        self::assertSame(0, $parentModel->{$relation}()->count());

        $this->assertResponseContent(['attached' => [], 'detached' => [], 'updated' => []], $response, $exact);
    }

    protected function assertNoResourcesToggled($response, string $relation, Model $parentModel,int $expectedCount, bool $exact = true): void
    {
        self::assertSame($expectedCount, $parentModel->{$relation}()->count());

        $this->assertResponseContent(['attached' => [], 'detached' => []], $response, $exact);
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
        self::assertSame($expected, $actual);
    }

    protected function buildSyncMap(array $attached = [], array $detached = [], array $updated = [], array $remained = []): array
    {
        return [
            'attached' => $attached,
            'detached' => $detached,
            'updated' => $updated,
            'remained' => $remained
        ];
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
                (float) app()->version() >= 8.0 ? '&laquo; Previous' : 'Previous',
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
            (float) app()->version() >= 8.0 ? 'Next &raquo;' : 'Next',
            false
        );

        return $links;
    }

    protected function buildMetaLink(?string $url, $label, bool $active): array
    {
        return [
            'url' => $url,
            'label' => (string) $label,
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