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

    public function filterableBy(): array
    {
        return ['pivot.custom_name', 'pivot.created_at'];
    }

    public function sortableBy(): array
    {
        return ['pivot.custom_name'];
    }

    public function includes(): array
    {
        return ['users'];
    }

    public function aggregates(): array
    {
        return ['users'];
    }
}
