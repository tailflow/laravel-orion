<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Repositories;

use Orion\Repositories\BaseRepository;
use Orion\Tests\Fixtures\App\Models\Post;

class PostBaseRepository extends BaseRepository
{
    public function model(): string
    {
        return Post::class;
    }
}
