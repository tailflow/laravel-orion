<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Company extends Model
{
    use AppliesDefaultOrder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
