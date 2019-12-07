<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\History;

class HistoryController extends Controller
{
    /**
     * @var string|null $model
     */
    protected static $model = History::class;
}
