<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\Specs\Component;
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
     */
    public function build(): array
    {
        $resources = $this->resourcesCacheStore->getResources();

        $components = collect([]);

        foreach ($resources as $resource) {
            $resourceModelClass = app()->make($resource->controller)->resolveResourceModelClass();
            $resourceModel = app()->make($resourceModelClass);

            $baseModelComponent = $this->buildBaseModelComponent($resourceModel);
            $modelResourceComponent = $this->buildModelResourceComponent($resourceModel);

            $components->put($baseModelComponent->title, $baseModelComponent);
            $components->put($modelResourceComponent->title, $modelResourceComponent);
        }

        return $components->toArray();
    }

    /**
     * @param Model $resourceModel
     * @return Component
     */
    public function buildBaseModelComponent(Model $resourceModel): Component
    {
        $component = new Component();
        $component->title = class_basename($resourceModel);
        $component->type = 'object';
        $component->properties = $this->getPropertiesFromSchema(
            $resourceModel,
            [
                $resourceModel->getKeyName(),
                'created_at',
                'updated_at',
                'deleted_at',
            ]
        );

        return $component;
    }

    /**
     * @param Model $resourceModel
     * @return Component
     */
    public function buildModelResourceComponent(Model $resourceModel): Component
    {
        $resourceComponentBaseName = class_basename($resourceModel);

        $component = new Component();
        $component->title = "{$resourceComponentBaseName}Resource";
        $component->type = 'object';
        //TODO: extract key and timestamps to a shared schema component
        //TODO: handle soft deletes
        $component->properties = [
            'allOf' => [
                ['$ref' => "#/components/schemas/{$resourceComponentBaseName}Resource"],
                [
                    'type' => 'object',
                    'properties' => [
                        $resourceModel->getKeyName() => [
                            'type' => $resourceModel->getKeyType() === 'int' ? 'integer' : 'string',
                        ],
                        'created_at' => [
                            'type' => 'string',
                            'format' => 'date-time',
                        ],
                        'updated_at' => [
                            'type' => 'string',
                            'format' => 'date-time',
                        ],
                    ],
                ],
            ],
        ];

        return $component;
    }

    public function getPropertiesFromSchema(Model $resourceModel, array $exclude = []): array
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
    public function getSchemaColumns(Model $resourceModel): array
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
