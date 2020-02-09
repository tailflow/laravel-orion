<?php

namespace Orion\Tests\Unit\Concerns;

use Orion\Concerns\DisableAuthorization;
use Orion\Concerns\HandlesAuthorization;
use Orion\Tests\Unit\TestCase;

class HandlesAuthorizationTest extends TestCase
{
    /** @test */
    public function authorization_is_not_required_if_disable_authorization_property_is_defined()
    {
        $stub = new HandlesAuthorizationStubWithoutAuthorization();

        $this->assertFalse($stub->authorizationRequired());
    }

    /** @test */
    public function authorization_is_required_if_disable_authorization_property_is_missing()
    {
        $stub = new HandlesAuthorizationStubWithAuthorization();

        $this->assertTrue($stub->authorizationRequired());
    }
}

class HandlesAuthorizationStubWithoutAuthorization
{
    use HandlesAuthorization, DisableAuthorization;
}


class HandlesAuthorizationStubWithAuthorization
{
    use HandlesAuthorization;
}
