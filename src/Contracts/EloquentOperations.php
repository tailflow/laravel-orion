<?php


namespace Orion\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface EloquentOperations
 *
 * @package Orion\Contracts
 */
interface EloquentOperations
{
    public function search(array $params): LengthAwarePaginator|Collection|array;
    public function list(array $params, $perPage = 10): LengthAwarePaginator|Collection|array;
    public function getById(array $params): Model|array;
    public function create(array $request): Model;
    public function update(array $request): Model;
    public function delete(array $params): Model;
    public function seed(array $request): Model;
    public function getAvailability();
    public function setModel(string $model);
}
