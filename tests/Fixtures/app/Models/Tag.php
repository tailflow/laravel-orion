<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Class Tag
 * @package Orion\Tests\Fixtures\App\Models
 *
 * @property string $name
 * @property string|null $description
 * @property int|null $priority
 */
class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'team_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function meta()
    {
        return $this->hasOne(TagMeta::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithPriority($query)
    {
        return $query->whereNotNull('priority');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutPriority($query)
    {
        return $query->whereNull('priority');
    }
}
