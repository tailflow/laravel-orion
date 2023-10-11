<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\User;

class UserNotificationsController extends RelationController
{
    public function model(): string
    {
        return User::class;
    }

    public function relation(): string
    {
        return 'notifications';
    }

    protected array $pivotJson = ['meta'];

    public function includes(): array
    {
        return ['users'];
    }
}
