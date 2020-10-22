<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\Company;

class CompanyTeamsController extends RelationController
{
    protected $model = Company::class;

    protected $relation = 'teams';

    protected function includes(): array
    {
        return ['company'];
    }
}