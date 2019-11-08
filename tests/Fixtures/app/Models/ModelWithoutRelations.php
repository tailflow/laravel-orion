<?php


namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelWithoutRelations extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description'
    ];
}
