<?php

namespace Orion\Tests\Fixtures\App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait AppliesDefaultOrder
{
    protected static function boot()
    {
        parent::boot();
        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('id', 'asc');
        });
    }
}