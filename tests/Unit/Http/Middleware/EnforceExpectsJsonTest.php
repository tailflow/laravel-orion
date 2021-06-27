<?php

namespace Orion\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Orion\Http\Middleware\EnforceExpectsJson;
use Orion\Tests\Unit\TestCase;

class EnforceExpectsJsonTest extends TestCase
{
    /** @test */
    public function adding_application_json_to_accept_header(): void
    {
        $request = Request::create('/api/posts');

        (new EnforceExpectsJson())->handle(
            $request,
            function ($processedRequest) {
                $this->assertTrue($processedRequest->expectsJson());
            }
        );
    }

    /** @test */
    public function preserving_existing_accept_header_content_types(): void
    {
        $request = Request::create('/api/posts');
        $request->headers->set('Accept', 'application/xml');

        (new EnforceExpectsJson())->handle(
            $request,
            function ($processedRequest) {
                $this->assertSame('application/json, application/xml', $processedRequest->header('Accept'));
            }
        );
    }
}
