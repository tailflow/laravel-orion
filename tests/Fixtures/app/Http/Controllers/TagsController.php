<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Tag;

class TagsController extends Controller
{
    use DisableAuthorization;

    /**
     * @var string|null $model
     */
    protected static $model = Tag::class;

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy()
    {
        return ['name'];
    }
}
