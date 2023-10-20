<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class PostImage extends Model
{
    use SoftDeletes, AppliesDefaultOrder;

    protected $fillable = ['path'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
