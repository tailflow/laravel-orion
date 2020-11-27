<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class PostMeta extends Model
{
    use AppliesDefaultOrder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['notes'];

    /**
     * @return BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
