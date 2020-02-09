<?php

namespace Orion\Tests\Unit\Concerns;

use Orion\Concerns\HandlesAuthentication;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Unit\TestCase;

class HandlesAuthenticationTest extends TestCase
{
    /** @test */
    public function resolving_user_with_api_guard()
    {
        $user = new User(['name' => 'test user']);
        $this->actingAs($user, 'api');

        $stub = new HandlesAuthenticationStub();
        $resolvedUser = $stub->resolveUser();

        $this->assertTrue($user->is($resolvedUser));
    }

    /** @test */
    public function resolving_user_with_other_guards()
    {
        $user = new User(['name' => 'test user']);
        $this->actingAs($user, 'web');

        $stub = new HandlesAuthenticationStub();
        $resolvedUser = $stub->resolveUser();

        $this->assertFalse($user->is($resolvedUser));
    }
}

class HandlesAuthenticationStub
{
    use HandlesAuthentication;
}
