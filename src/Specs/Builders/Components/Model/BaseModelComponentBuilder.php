<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Model;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Specs\Builders\Components\ModelComponentBuilder;
use Orion\ValueObjects\Specs\Component;

use function class_basename;

class BaseModelComponentBuilder extends ModelComponentBuilder
{
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

        $excludedColumns = $this->resolveExcludedColumns($resourceModel);

        $component->properties = $this->getPropertiesFromSchema(
            $resourceModel,
            $excludedColumns,
        );

        return $component;
    }

    /**
     * @param Model $resourceModel
     * @param array $excludedColumns
     * @return array
     * @throws Exception
     */
    protected function getPropertiesFromSchema(Model $resourceModel, array $excludedColumns = []): array
    {
        $columns = $this->schemaManager->getSchemaColumns($resourceModel);

        return collect($columns)
            ->filter(
                function (Column $column) use ($excludedColumns) {
                    return !in_array($column->getName(), $excludedColumns, true);
                }
            )->filter(
                function (Column $column) use ($resourceModel) {
                    return $resourceModel->isFillable($column->getName());
                }
            )->map(
                function (Column $column) use ($resourceModel) {
                    $propertyClass = $this->schemaManager->resolveSchemaPropertyClass($column, $resourceModel);

                    return $this->propertyBuilder->build($column, $propertyClass);
                }
            )->values()
            ->keyBy('name')
            ->toArray();
    }

    /**
     * @param Model $resourceModel
     * @return array
     */
    protected function resolveExcludedColumns(Model $resourceModel): array
    {
        $excludedColumns = [
            $resourceModel->getKeyName(),
            'created_at',
            'updated_at',
        ];

        if (method_exists($resourceModel, 'trashed')) {
            /** @var SoftDeletes $resourceModel */
            $excludedColumns[] = $resourceModel->getDeletedAtColumn();
        }

        return $excludedColumns;
    }
}
