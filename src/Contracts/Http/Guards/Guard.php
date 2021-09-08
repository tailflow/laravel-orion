<?php

declare(strict_types=1);

namespace Orion\Contracts\Http\Guards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface Guard
{
    public function guardEntity(Model $entity, GuardOptions $options): Model;

    public function guardCollection(Collection $collection, GuardOptions $options): Collection;
}
