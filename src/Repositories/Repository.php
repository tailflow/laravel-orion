<?php

declare(strict_types=1);

namespace Orion\Repositories;

class Repository extends BaseRepository
{
    protected string $model;

    public function model(): string
    {
        return '';
    }

    public function setModel(string $model): Repository
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
