<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\Specs\Component;
use Orion\ValueObjects\Specs\Schema\Components\ResourceLinksComponent;
use Orion\ValueObjects\Specs\Schema\Components\ResourceMetaComponent;
use Orion\ValueObjects\Specs\Schema\Components\ResourceTimestampsComponent;
use Orion\ValueObjects\Specs\Schema\Components\SoftDeletableResourceTimestampsComponent;
use Orion\ValueObjects\Specs\Schema\Properties\BooleanSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\DateTimeSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\IntegerSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\NumberSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\StringSchemaProperty;

class ComponentsBuilder
{
    /**
     * @var ResourcesCacheStore
     */
    protected $resourcesCacheStore;
    /**
     * @var PropertyBuilder
     */
    protected $propertyBuilder;

    /**
     * ComponentsBuilder constructor.
     *
     * @param ResourcesCacheStore $resourcesCacheStore
     * @param PropertyBuilder $propertyBuilder
     */
    public function __construct(ResourcesCacheStore $resourcesCacheStore, PropertyBuilder $propertyBuilder)
    {
        $this->resourcesCacheStore = $resourcesCacheStore;
        $this->propertyBuilder = $propertyBuilder;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function build(): array
    {
        $resources = $this->resourcesCacheStore->getResources();

        $components = collect([]);

        //TODO: use decorators?
        $components = $this->buildModelComponents($components, $resources);
        $components = $this->buildSharedComponents($components);

        return $components->toArray();
    }

    /**
     * @param Model $resourceModel
     * @return Component
     * @throws Exception
     */
    protected function buildBaseModelComponent(Model $resourceModel): Component
    {
        $component = new Component();
        $component->title = class_basename($resourceModel);
        $component->type = 'object';

        $excludedColumns = [
            $resourceModel->getKeyName(),
            'created_at',
            'updated_at',
        ];

        if (method_exists($resourceModel, 'trashed')) {
            $excludedColumns[] = $resourceModel->getDeletedAtColumn();
        }

        $component->properties = $this->getPropertiesFromSchema(
            $resourceModel,
            $excludedColumns,
        );

        return $component;
    }

    /**
     * @param Model $resourceModel
     * @return Component
     */
    protected function buildModelResourceComponent(Model $resourceModel): Component
    {
        $resourceComponentBaseName = class_basename($resourceModel);
        $timestampsComponent = method_exists($resourceModel, 'trashed') ? 'SoftDeletableResourceTimestampsComponent' : 'ResourceTimestampsComponent';

        $component = new Component();
        $component->title = "{$resourceComponentBaseName}Resource";
        $component->type = 'object';
        $component->properties = [
            'allOf' => [
                ['$ref' => "#/components/schemas/{$resourceComponentBaseName}Resource"],
                ['$ref' => "#/components/schemas/{$timestampsComponent}"],
                [
                    'type' => 'object',
                    'properties' => [
                        $resourceModel->getKeyName() => [
                            'type' => $resourceModel->getKeyType() === 'int' ? 'integer' : 'string',
                        ],
                    ],
                ],
            ],
        ];

        return $component;
    }

    /**
     * @param Collection $components
     * @param array $resources
     * @return Collection
     * @throws Exception
     * @throws BindingResolutionException
     */
    protected function buildModelComponents(Collection $components, array $resources): Collection
    {
        foreach ($resources as $resource) {
            $resourceModelClass = app()->make($resource->controller)->resolveResourceModelClass();
            $resourceModel = app()->make($resourceModelClass);

            $baseModelComponent = $this->buildBaseModelComponent($resourceModel);
            $components->put($baseModelComponent->title, $baseModelComponent);

            $modelResourceComponent = $this->buildModelResourceComponent($resourceModel);
            $components->put($modelResourceComponent->title, $modelResourceComponent);
        }

        return $components;
    }

    /**
     * @param Collection $components
     * @return Collection
     */
    protected function buildSharedComponents(Collection $components): Collection
    {
        $resourceLinksComponent = new ResourceLinksComponent();
        $components->put($resourceLinksComponent->title, $resourceLinksComponent);

        $resourceMetaComponent = new ResourceMetaComponent();
        $components->put($resourceMetaComponent->title, $resourceMetaComponent);

        $resourceTimestampsComponent = new ResourceTimestampsComponent();
        $components->put($resourceTimestampsComponent->title, $resourceTimestampsComponent);

        $softDeletableResourceTimestampComponent = new SoftDeletableResourceTimestampsComponent();
        $components->put($softDeletableResourceTimestampComponent->title, $softDeletableResourceTimestampComponent);

        return $components;
    }

    /**
     * @param Model $resourceModel
     * @param array $exclude
     * @return array
     * @throws Exception
     */
    protected function getPropertiesFromSchema(Model $resourceModel, array $exclude = []): array
    {
        $columns = $this->getSchemaColumns($resourceModel);

        return collect($columns)->filter(
            function (Column $column) use ($exclude) {
                return !in_array($column->getName(), $exclude, true);
            }
        )->map(
            function (Column $column) use ($resourceModel) {
                $propertyClass = $this->resolveSchemaPropertyClass($column, $resourceModel);

                return $this->propertyBuilder->build($column, $propertyClass);
            }
        )->values()->toArray();
    }

    /**
     * @throws Exception
     */
    protected function getSchemaColumns(Model $resourceModel): array
    {
        $table = $resourceModel->getConnection()->getTablePrefix().$resourceModel->getTable();
        $schema = $resourceModel->getConnection()->getDoctrineSchemaManager();

        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

        $database = null;
        if (strpos($table, '.')) {
            [$database, $table] = explode('.', $table);
        }

        return $schema->listTableColumns($table, $database) ?? [];
    }

    /**
     * @param Column $column
     * @param Model $resourceModel
     * @return string
     */
    protected function resolveSchemaPropertyClass(Column $column, Model $resourceModel): string
    {
        if (in_array($column->getName(), $resourceModel->getDates(), true)) {
            return DateTimeSchemaProperty::class;
        }

        switch ($column->getType()->getName()) {
            case 'integer':
            case 'bigint':
            case 'smallint':
                return IntegerSchemaProperty::class;
            case 'boolean':
                return BooleanSchemaProperty::class;
            case 'float':
                return NumberSchemaProperty::class;
            default:
                return StringSchemaProperty::class;
        }
    }
}
