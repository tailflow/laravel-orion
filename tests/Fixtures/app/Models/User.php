<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class User extends Authenticatable
{
    use AppliesDefaultOrder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withPivot('meta', 'references', 'custom_name');
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class)->withPivot('meta');
    }
}
