<?php

namespace Orion\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Orion\Http\Middleware\EnforceExpectsJson;
use Orion\Tests\Unit\TestCase;

class EnforceExpectsJsonTest extends TestCase
{
    /** @test */
    public function adding_accept_header()
    {
        $request = Request::create('/api/posts');

        (new EnforceExpectsJson())->handle($request, function ($processedRequest) {
            $this->assertEquals('application/json', $processedRequest->header('Accept'));
        });
    }
}
