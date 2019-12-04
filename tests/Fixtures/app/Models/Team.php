<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function supplierHistory()
    {
        return $this->hasOneThrough(History::class, Supplier::class);
    }
}
