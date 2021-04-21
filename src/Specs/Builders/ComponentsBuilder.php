<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Orion\Specs\Builders\Components\Properties\BooleanPropertyBuilder;
use Orion\Specs\Builders\Components\Properties\DateTimePropertyBuilder;
use Orion\Specs\Builders\Components\Properties\NumberPropertyBuilder;
use Orion\Specs\Builders\Components\Properties\IntegerPropertyBuilder;
use Orion\Specs\Builders\Components\Properties\StringPropertyBuilder;
use Orion\Specs\Builders\Components\PropertyBuilder;
use Orion\Specs\ResourcesCacheStore;
use Orion\ValueObjects\Specs\Component;

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

        return collect($columns)->map(
            function (Column $column) use ($resourceModel) {
                $propertyBuilder = $this->resolvePropertyBuilder($column, $resourceModel);
                $baseProperty = $propertyBuilder->makeBaseProperty($column);

                return $propertyBuilder->build($column, $baseProperty);
            }
        )->toArray();
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

    protected function resolvePropertyBuilder(Column $column, Model $resourceModel): PropertyBuilder
    {
        if (in_array($column->getName(), $resourceModel->getDates(), true)) {
            return app()->make(DateTimePropertyBuilder::class);
        }

        switch ($column->getType()->getName()) {
            case 'integer':
            case 'bigint':
            case 'smallint':
                return app()->make(IntegerPropertyBuilder::class);
            case 'boolean':
                return app()->make(BooleanPropertyBuilder::class);
            case 'float':
                return app()->make(NumberPropertyBuilder::class);
            default:
                return app()->make(StringPropertyBuilder::class);
        }
    }
}
