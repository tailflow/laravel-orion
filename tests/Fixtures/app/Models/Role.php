<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Role extends Model
{
    use AppliesDefaultOrder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'description'];

    protected $casts = [
        'deprecated' => 'boolean'
    ];

    /**
     * The roles that belong to the user.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('meta', 'references', 'custom_name');
    }
}