<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Post extends Model
{
    use SoftDeletes, AppliesDefaultOrder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'body', 'user_id', 'stars'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'options' => 'array',
        'stars' => 'float',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'publish_at',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function meta(): HasOne
    {
        return $this->hasOne(PostMeta::class);
    }

    public function image(): HasOne
    {
        return $this->hasOne(PostImage::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publish_at', '<', Carbon::now());
    }

    public function scopePublishedAt(Builder $query, string $dateTime): Builder
    {
        return $query->where('publish_at', $dateTime);
    }

    public function scopeWithMeta(Builder $query): Builder
    {
        return $query->whereNotNull('meta');
    }
}
