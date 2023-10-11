<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Company;

class CompanyTeamsController extends RelationController
{
    public function model(): string
    {
        return Company::class;
    }

    public function relation(): string
    {
        return 'teams';
    }

    public function includes(): array
    {
        return ['company'];
    }

    public function aggregates(): array
    {
        return ['company'];
    }
}
