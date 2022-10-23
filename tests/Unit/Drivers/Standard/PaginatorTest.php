<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Illuminate\Support\Facades\Gate;
use Orion\Drivers\Standard\Paginator;
use Orion\Exceptions\MaxPaginationLimitExceededException;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;
use Orion\Tests\Unit\TestCase;

class PaginatorTest extends TestCase
{
    /** @test */
    public function resolving_default_pagination_limit()
    {
        $paginator = new Paginator(15, 500);

        $this->assertSame(15, $paginator->resolvePaginationLimit(Request::create('/api/posts')));
    }

    /** @test */
    public function resolving_pagination_limit_from_request()
    {
        $paginator = new Paginator(15, 500);
        $request = Request::create('/api/posts');
        $request->query->set('limit', 30);

        $this->assertSame(30, $paginator->resolvePaginationLimit($request));
    }

    /** @test */
    public function falling_back_to_default_pagination_limit()
    {
        $paginator = new Paginator(15, 500);
        $request = Request::create('/api/posts');
        $request->query->set('limit', 0);

        $this->assertSame(15, $paginator->resolvePaginationLimit($request));
    }

    /** @test */
    public function getting_a_list_of_resources_with_exceeded_pagination_limit(): void
    {
        $paginator = new Paginator(15, 500);
        $request = Request::create('/api/posts');
        $request->query->set('limit', 501);

        $this->expectException(MaxPaginationLimitExceededException::class);

        $paginator->resolvePaginationLimit($request);
    }
}
