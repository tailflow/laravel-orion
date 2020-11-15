<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\User;

class UserRolesController extends RelationController
{
    protected $model = User::class;

    protected $relation = 'roles';

    protected $pivotJson = ['meta', 'references'];

    protected $pivotFillable = ['custom_name', 'references'];

    protected function includes(): array
    {
        return ['users'];
    }
}