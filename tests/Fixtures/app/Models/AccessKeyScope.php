<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class AccessKeyScope extends Model
{
    use AppliesDefaultOrder, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'scope',
        'description',
    ];

    public function accessKey()
    {
        return $this->belongsTo(AccessKey::class);
    }
}
