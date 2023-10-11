<?php

declare(strict_types=1);

namespace Orion\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

abstract class BaseRepository
{
    abstract public function model(): string;

    public function make(array $attributes = []): Model
    {
        $model = $this->getModel();

        return new $model($attributes);
    }

    public function store(array $attributes): Model
    {
        $entity = $this->make();

        $this->beforeStore($entity, $attributes)
            ->beforeSave($entity, $attributes)
            ->performFill($entity, $attributes)
            ->performStore($entity)
            ->afterSave($entity)
            ->afterStore($entity);

        return $entity;
    }

    public function performStore(Model $entity): static
    {
        $entity->save();

        return $this;
    }

    public function performFill(Model $entity, array $attributes): static
    {
        $entity->fill(
            Arr::except($attributes, array_keys($entity->getDirty()))
        );

        return $this;
    }

    public function beforeStore(Model $entity, array &$attributes): static
    {
        return $this;
    }

    public function beforeSave(Model $entity, array &$attributes): static
    {
        return $this;
    }

    public function afterStore(Model $entity): static
    {
        return $this;
    }

    public function afterSave(Model $entity): static
    {
        return $this;
    }

    public function getModel(): string
    {
        return $this->model();
    }
}
