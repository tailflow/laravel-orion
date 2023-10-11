<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\User;

class UserPostsController extends RelationController
{
    public function model(): string
    {
        return User::class;
    }

    public function relation(): string
    {
        return 'posts';
    }

    public function includes(): array
    {
        return ['user'];
    }
}
