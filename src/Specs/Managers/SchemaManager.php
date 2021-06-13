<?php

declare(strict_types=1);

namespace Orion\Specs\Managers;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Orion\ValueObjects\Specs\Schema\Properties\BooleanSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\DateTimeSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\IntegerSchemaProperty;
use Orion\ValueObjects\Specs\Schema\Properties\NumberSchemaProperty;
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
