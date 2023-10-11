<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;

class UsersController extends Controller
{
    public function model(): string
    {
        return User::class;
    }

    public function aggregates(): array
    {
        return ['posts.stars', 'posts'];
    }

    public function includes(): array
    {
        return ['posts'];
    }

    public function filterableBy(): array
    {
        return ['name','posts.stars'];
    }


}
