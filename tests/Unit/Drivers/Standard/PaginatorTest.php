<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Orion\Drivers\Standard\Paginator;
use Orion\Http\Requests\Request;
use Orion\Tests\Unit\TestCase;

class PaginatorTest extends TestCase
{
    /** @test */
    public function resolving_default_pagination_limit()
    {
        $paginator = new Paginator(15);

        $this->assertSame(15, $paginator->resolvePaginationLimit(Request::create('/api/posts')));
    }

    /** @test */
    public function resolving_pagination_limit_from_request()
    {
        $paginator = new Paginator(15);
        $request = Request::create('/api/posts');
        $request->query->set('limit', 30);

        $this->assertSame(30, $paginator->resolvePaginationLimit($request));
    }

    /** @test */
    public function falling_back_to_default_pagination_limit()
    {
        $paginator = new Paginator(15);
        $request = Request::create('/api/posts');
        $request->query->set('limit', 0);

        $this->assertSame(15, $paginator->resolvePaginationLimit($request));
    }
}
