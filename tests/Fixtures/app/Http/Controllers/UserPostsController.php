<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\User;

class UserPostsController extends RelationController
{
    protected $model = User::class;

    protected $relation = 'posts';

    protected function includes(): array
    {
        return ['user'];
    }
}