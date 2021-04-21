<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Carbon\Carbon;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\Specs\Component;
use Orion\ValueObjects\Specs\Schema\SchemaColumn;
use Orion\ValueObjects\Specs\Schema\SchemaProperty;

class ComponentsBuilder
{
    /**
     * @var ResourcesCacheStore
     */
    protected $resourcesCacheStore;

    public function __construct(ResourcesCacheStore $resourcesCacheStore)
    {
        $this->resourcesCacheStore = $resourcesCacheStore;
    }

    public function build(): array
    {
        $resources = $this->resourcesCacheStore->getResources();

        $components = collect([]);

        foreach ($resources as $resource) {
            $resourceModelClass = app()->make($resource->controller)->resolveResourceModelClass();

            $component = new Component();
            $component->title = class_basename($resourceModelClass);
            $component->type = 'object';
            $component->properties = $this->getPropertiesFromSchema($resourceModelClass);

            $components->put($component->title, $component);
        }

        return $components->toArray();
    }

    public function getPropertiesFromSchema(string $resourceModelClass): array
    {
        $resourceModel = app()->make($resourceModelClass);

        $columns = $this->getSchemaColumns($resourceModel);
        $columns = $this->castSchemaColumnTypes($columns, $resourceModel);

        return $this->buildPropertiesFromSchemaColumns($columns);
    }

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

    public function castSchemaColumnTypes(array $columns, Model $resourceModel): array
    {
        return collect($columns)->map(
            function (Column $column) use ($resourceModel) {
                $name = $column->getName();

                if (in_array($name, $resourceModel->getDates(), true)) {
                    $type = Carbon::class;
                } else {
                    $type = $column->getType()->getName();
                    switch ($type) {
                        case 'string':
                        case 'text':
                        case 'date':
                        case 'time':
                        case 'guid':
                        case 'datetimetz':
                        case 'datetime':
                        case 'decimal':
                            $type = 'string';
                            break;
                        case 'integer':
                        case 'bigint':
                        case 'smallint':
                            $type = 'integer';
                            break;
                        case 'boolean':
                            $type = 'boolean';
                            break;
                        case 'float':
                            $type = 'float';
                            break;
                        default:
                            $type = 'mixed';
                            break;
                    }
                }

                $schemaColumn = new SchemaColumn();
                $schemaColumn->name = $column->getName();
                $schemaColumn->type = $type;

                return $schemaColumn;
            }
        )->toArray();
    }

    public function buildPropertiesFromSchemaColumns(array $columns): array
    {
        return collect($columns)->map(
            function (SchemaColumn $column) {
                $property = new SchemaProperty();
                $property->name = $column->name;
                $property->type = $column->type;

                return $property;
            }
        )->toArray();
    }
}
