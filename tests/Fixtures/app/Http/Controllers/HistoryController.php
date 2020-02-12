<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Http\Resources\TagMetaResource;
use Orion\Tests\Fixtures\App\Models\History;

class HistoryController extends Controller
{
    /**
     * @var string|null $model
     */
    protected $model = History::class;

    /**
     * @var string $resource
     */
    protected $resource = TagMetaResource::class;
}
