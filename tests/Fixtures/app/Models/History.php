<?php


namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
