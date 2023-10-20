<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Category extends Model
{
    use SoftDeletes, AppliesDefaultOrder;

    protected $fillable = [
        'name'
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
