<?php

declare(strict_types=1);

namespace Orion\Specs\Managers;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Orion\ValueObjects\Specs\Schema\Properties\AnySchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\ArraySchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\BinarySchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\BooleanSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\DateSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\DateTimeSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\IntegerSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\NumberSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\ObjectSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\StringSchemaProperty;

class SchemaManager
{
    /**
     * @throws Exception
     */
    public function getSchemaColumns(Model $resourceModel): array
    {
        $table = $resourceModel->getConnection()->getTablePrefix().$resourceModel->getTable();

        $database = null;

        if (strpos($table, '.')) {
            [$database, $table] = explode('.', $table);
        }

        if ((float) app()->version() < 11.0) {
            $schema = $resourceModel->getConnection()->getDoctrineSchemaManager();

            $databasePlatform = $schema->getDatabasePlatform();
            $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

            return collect($schema->listTableColumns($table, $database))->map(function(Column $column) {
                return [
                    'name' => $column->getName(),
                    'type' => $column->getType()->getName(),
                    'nullable' => !$column->getNotnull()
                ];
            })->toArray();
        }

        return $resourceModel->getConnection()->getSchemaBuilder()->getColumns($table);
    }

    /**
     * @param array $column
     * @param Model $resourceModel
     * @return string
     */
    public function resolveSchemaPropertyClass(array $column, Model $resourceModel): string
    {
        if (in_array($column['name'], $resourceModel->getDates(), true)) {
            return DateTimeSchemaProperty::class;
        }

        switch ($column['type']) {
            case strpos($column['type'], 'int') !== false:
                return IntegerSchemaProperty::class;
            case 'float':
            case 'decimal':
                return NumberSchemaProperty::class;
            case strpos($column['type'], 'bool') !== false:
                return BooleanSchemaProperty::class;
            case strpos($column['type'], 'char') !== false:
            case strpos($column['type'], 'time') !== false:
            case 'text':
            case 'string':
            case 'guid':
                return StringSchemaProperty::class;
            case 'date':
            case 'date_immutable':
                return DateSchemaProperty::class;
            case 'datetime':
            case 'datetime_immutable':
                return DateTimeSchemaProperty::class;
            case 'array':
                return ArraySchemaProperty::class;
            case strpos($column['type'], 'json') !== false:
                return ObjectSchemaProperty::class;
            case 'binary':
            case 'blob':
                return BinarySchemaProperty::class;
            default:
                return AnySchemaProperty::class;
        }
    }
}
