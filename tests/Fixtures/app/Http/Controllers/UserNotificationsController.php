<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\User;

class UserNotificationsController extends RelationController
{
    protected $model = User::class;

    protected $relation = 'notifications';

    protected $pivotJson = ['meta'];

    protected function includes(): array
    {
        return ['users'];
    }
}