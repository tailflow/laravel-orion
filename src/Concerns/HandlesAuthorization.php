<?php

namespace Orion\Concerns;

trait HandlesAuthorization
{
    /**
     * Determine whether authorization is required or not to perform the action.
     *
     * @return bool
     */
    public function authorizationRequired()
    {
        return !property_exists($this, 'authorizationDisabled');
    }

}
