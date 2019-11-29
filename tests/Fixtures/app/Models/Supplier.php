<?php


namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
