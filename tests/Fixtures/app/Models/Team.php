<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function supplierHistory()
    {
        return $this->hasOneThrough(History::class, Supplier::class);
    }
}
