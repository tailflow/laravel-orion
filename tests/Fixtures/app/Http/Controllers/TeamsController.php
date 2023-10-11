<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\Team;

class TeamsController extends Controller
{
    public function model(): string
    {
        return Team::class;
    }

    public function filterableBy() : array
    {
        return ['*', 'company.*'];
    }

    public function includes(): array
    {
        return ['*'];
    }
}
