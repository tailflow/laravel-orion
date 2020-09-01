<?php


namespace Orion\Concerns;

use Illuminate\Support\Facades\Auth;

trait HandlesAuthentication
{
    protected $guard = 'api';
    /**
     * Retrieves currently authenticated user based on the guard.
     *
     * @return \Illuminate\Foundation\Auth\User|null
     */
    public function resolveUser()
    {
        return Auth::guard($this->guard)->user();
    }
}
