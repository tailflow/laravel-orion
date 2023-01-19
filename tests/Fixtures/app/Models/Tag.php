<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Tag extends Model
{
    use AppliesDefaultOrder;

    protected $fillable = [
        'name'
    ];

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
