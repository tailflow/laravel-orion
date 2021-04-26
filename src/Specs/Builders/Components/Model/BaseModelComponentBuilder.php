<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Model;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Specs\Builders\PropertyBuilder;
use Orion\ValueObjects\Specs\Component;
use Orion\ValueObjects\Specs\Schema\Properties\BooleanSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\DateTimeSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\IntegerSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\NumberSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\StringSchemaProperty;

use function class_basename;
use function collect;

class BaseModelComponentBuilder
{
    /**
     * @var PropertyBuilder
     */
    protected $propertyBuilder;

    public function __construct(PropertyBuilder $propertyBuilder)
    {
        $this->propertyBuilder = $propertyBuilder;
    }

    /**
     * @param Model $resourceModel
     * @return Component
     * @throws Exception
     */
    public function build(Model $resourceModel): Component
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
            /** @var SoftDeletes $resourceModel */
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
