<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Team extends Model
{
    use AppliesDefaultOrder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
