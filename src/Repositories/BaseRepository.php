<?php

declare(strict_types=1);

namespace Orion\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Orion\Concerns\HandlesTransactions;
use Throwable;

abstract class BaseRepository
{
    use HandlesTransactions;

    abstract public function model(): string;

    public function make(array $attributes = []): Model
    {
        $model = $this->getModel();

        return new $model($attributes);
    }

    /**
     * @param array $attributes
     * @param array $relations
     * @return Model
     * @throws Exception
     */
    public function store(array $attributes, array $relations = []): Model
    {
        $entity = $this->make();

        try {
            $this->startTransaction();

            $this->beforeStore($entity, $attributes)
                ->beforeSave($entity, $attributes)
                ->performFill($entity, $attributes, $relations)
                ->performStore($entity)
                ->afterSave($entity)
                ->afterStore($entity);

            $this->commitTransaction();
        } catch (Throwable $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }

        return $entity;
    }

    /**
     * @param Model $entity
     * @param array $attributes
     * @return Model
     * @throws Exception
     */
    public function update(Model $entity, array $attributes): Model
    {
        try {
            $this->startTransaction();

            $this->beforeUpdate($entity, $attributes)
                ->beforeSave($entity, $attributes)
                ->performFill($entity, $attributes)
                ->performUpdate($entity)
                ->afterSave($entity)
                ->afterUpdate($entity);

            $this->commitTransaction();
        } catch (Throwable $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }

        return $entity;
    }

    /**
     * @param Model $entity
     * @param bool $force
     * @return Model
     * @throws Exception
     */
    public function destroy(Model $entity, bool $force = false): Model
    {
        try {
            $this->startTransaction();

            $this->beforeDestroy($entity, $force)
                ->performDestroy($entity, $force)
                ->afterDestroy($entity);

            $this->commitTransaction();
        } catch (Throwable $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }

        return $entity;
    }

    /**
     * @param Model $entity
     * @return Model
     * @throws Exception
     */
    public function restore(Model $entity): Model
    {
        try {
            $this->beforeRestore($entity)
                ->performRestore($entity)
                ->afterRestore($entity);
        } catch (Throwable $exception) {
            $this->rollbackTransactionAndRaise($exception);
        }

        return $entity;
    }

    public function performStore(Model $entity): static
    {
        $entity->save();

        return $this;
    }

    public function performUpdate(Model $entity): static
    {
        $entity->save();

        return $this;
    }

    public function performFill(Model $entity, array $attributes, array $relations = []): static
    {
        $entity->fill(
            Arr::except($attributes, array_keys($entity->getDirty()))
        );

        foreach ($relations as $relation => $value) {
            $entity->setRelation($relation, $value);
        }

        return $this;
    }

    public function performDestroy(Model $entity, bool $force): static
    {
        if ($force) {
            $entity->forceDelete();
        } else {
            $entity->delete();
        }

        return $this;
    }

    public function performRestore(Model $entity): static
    {
        $entity->restore();

        return $this;
    }

    public function beforeStore(Model $entity, array &$attributes): static
    {
        return $this;
    }

    public function beforeUpdate(Model $entity, array &$attributes): static
    {
        return $this;
    }

    public function beforeSave(Model $entity, array &$attributes): static
    {
        return $this;
    }

    public function beforeDestroy(Model $entity, bool &$force): static
    {
        return $this;
    }

    public function beforeRestore(Model $entity): static
    {
        return $this;
    }

    public function afterStore(Model $entity): static
    {
        return $this;
    }

    public function afterUpdate(Model $entity): static
    {
        return $this;
    }

    public function afterSave(Model $entity): static
    {
        return $this;
    }

    public function afterDestroy(Model $entity): static
    {
        return $this;
    }

    public function afterRestore(Model $entity): static
    {
        return $this;
    }

    public function getModel(): string
    {
        return $this->model();
    }
}
