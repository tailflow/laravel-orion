<?php

namespace Orion\Tests\Unit\Concerns;

use Illuminate\Http\Response;
use Orion\Concerns\InteractsWithHooks;
use Orion\Tests\Unit\TestCase;

class InteractsWithHooksTest extends TestCase
{
    /** @test */
    public function hook_responds_if_result_is_a_response()
    {
        $stub = new InteractsWithHooksStub();

        $this->assertTrue($stub->hookResponds(new Response()));
    }

    /** @test */
    public function hook_does_not_respond()
    {
        $stub = new InteractsWithHooksStub();

        $this->assertFalse($stub->hookResponds([]));
    }
}

class InteractsWithHooksStub
{
    use InteractsWithHooks;
}
