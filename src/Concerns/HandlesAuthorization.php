<?php

namespace Orion\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Container\BindingResolutionException;

trait HandlesAuthorization
{
    /**
     * Return authorized response.
     *
     * @return Response
     */
    protected function authorized(): Response
    {
        return new Response('Authorized.');
    }

    /**
     * Determine whether authorization is required or not to perform the action.
     *
     * @return bool
     * @throws BindingResolutionException
     */
    protected function authorizationRequired(): bool
    {
        if (app()->bound('orion.authorizationRequired')) {
            return app()->make('orion.authorizationRequired');
        }

        return !property_exists($this, 'authorizationDisabled');
    }
}
