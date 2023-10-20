<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class User extends Authenticatable
{
    use AppliesDefaultOrder;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('meta', 'references', 'custom_name')
            ->withTimestamps()
            ->using(UserRole::class);
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class)->withPivot('meta');
    }
}
