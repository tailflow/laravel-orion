<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Category extends Model
{
    use SoftDeletes, AppliesDefaultOrder;

    protected $fillable = [
        'name'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at' //workaround for Laravel 5.7 - SoftDeletes trait adds deleted_at column to dates automatically since Laravel 5.8
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}