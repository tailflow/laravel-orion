<?php

declare(strict_types=1);

namespace Orion\Specs\Managers;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
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
    public function resolveSchemaPropertyClass(Column $column, Model $resourceModel): string
    {
        if (in_array($column->getName(), $resourceModel->getDates(), true)) {
            return DateTimeSchemaProperty::class;
        }

        switch ($column->getType()->getName()) {
            case Types::BIGINT:
            case Types::INTEGER:
            case Types::SMALLINT:
                return IntegerSchemaProperty::class;
            case Types::FLOAT:
            case Types::DECIMAL:
                return NumberSchemaProperty::class;
            case Types::BOOLEAN:
                return BooleanSchemaProperty::class;
            case Types::STRING:
            case Types::TEXT:
            case Types::ASCII_STRING:
            case Types::GUID:
            case Types::TIME_MUTABLE:
            case Types::TIME_IMMUTABLE:
                return StringSchemaProperty::class;
            case Types::DATE_MUTABLE:
            case Types::DATE_IMMUTABLE:
                return DateSchemaProperty::class;
            case Types::DATETIME_MUTABLE:
            case Types::DATETIME_IMMUTABLE:
                return DateTimeSchemaProperty::class;
            case Types::ARRAY:
            case Types::SIMPLE_ARRAY:
                return ArraySchemaProperty::class;
            case Types::OBJECT:
            case Types::JSON:
                return ObjectSchemaProperty::class;
            case Types::BINARY:
            case Types::BLOB:
                return BinarySchemaProperty::class;
            default:
                return AnySchemaProperty::class;
        }
    }
}
