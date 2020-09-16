<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Team;

class TeamsController extends Controller
{
    /**
     * @var string|null $model
     */
    protected $model = Team::class;

    protected function filterableBy() : array
    {
        return ['*', 'company.*'];
    }
}
