<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Product extends Model
{
    use SoftDeletes, AppliesDefaultOrder;

    protected $fillable = [
        'title'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
