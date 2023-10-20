<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Role extends Model
{
    use AppliesDefaultOrder;

    protected $fillable = ['name', 'description'];

    protected $casts = [
        'deprecated' => 'boolean'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('meta', 'references', 'custom_name')
            ->withTimestamps()
            ->using(UserRole::class);
    }
}
