<?php

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Orion\Tests\Fixtures\App\Http\Resources\SupplierCollectionResource;
use Orion\Tests\Fixtures\App\Http\Resources\TagMetaResource;

/**
 * Class Tag
 * @package Orion\Tests\Fixtures\App\Models
 *
 * @property string $name
 * @property string|null $description
 */
class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description'
    ];

    /**
     * @var string $resource
     */
    protected static $resource = TagMetaResource::class;

    /**
     * @var string $collectionResource
     */
    protected static $collectionResource = SupplierCollectionResource::class;

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
}
