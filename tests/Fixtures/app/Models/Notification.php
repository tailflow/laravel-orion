<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Notification extends Model
{
    use SoftDeletes, AppliesDefaultOrder;

    protected $fillable = ['text'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('meta');
    }
}