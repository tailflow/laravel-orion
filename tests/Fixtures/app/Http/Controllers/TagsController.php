<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Http\Requests\TagRequest;
use Orion\Tests\Fixtures\App\Models\Tag;

class TagsController extends Controller
{
    use DisableAuthorization;

    /**
     * @var string|null $model
     */
    protected $model = Tag::class;

    /**
     * @var string $request
     */
    protected $request = TagRequest::class;

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy()
    {
        return ['name', 'meta.key', 'team.name'];
    }

    /**
     * @return array
     */
    public function filterableBy()
    {
        return ['name', 'priority', 'meta.key'];
    }

    /**
     * @return array
     */
    public function searchableBy()
    {
        return ['name', 'meta.key'];
    }

    public function exposedScopes()
    {
        return ['withPriority', 'whereNameAndPriority'];
    }

    /**
     * The relations that are allowed to be included together with a resource.
     *
     * @return array
     */
    protected function includes()
    {
        return ['posts', 'team'];
    }
}
