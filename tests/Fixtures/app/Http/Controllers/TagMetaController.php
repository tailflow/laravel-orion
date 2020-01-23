<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Http\Resources\TagMetaResource;
use Orion\Tests\Fixtures\App\Models\TagMeta;

class TagMetaController extends Controller
{
    use DisableAuthorization;

    /**
     * @var string|null $model
     */
    protected static $model = TagMeta::class;

    /**
     * @var string $resource
     */
    protected static $resource = TagMetaResource::class;

    /**
     * The relations that are allowed to be included together with a resource.
     *
     * @return array
     */
    protected function includes()
    {
        return ['tag'];
    }
}
