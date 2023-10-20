<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class PostMeta extends Model
{
    use AppliesDefaultOrder;

    protected $fillable = ['name', 'title', 'notes'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
