<?php


namespace Orion\Concerns;

use Illuminate\Support\Facades\Auth;

trait HandlesAuthentication
{
    /**
     * Retrieves currently authenticated user based on the guard.
     *
     * @return \Illuminate\Foundation\Auth\User|null
     */
    protected function resolveUser()
    {
        return Auth::guard('api')->user();
    }
}
