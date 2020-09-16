<?php

namespace Orion\Testing;

use Illuminate\Contracts\Auth\Authenticatable;

trait InteractsWithAuthorization
{
    /**
     * Sets currently authenticated user to the provided one or creates it.
     *
     * @param Authenticatable|null $user
     * @param string $driver
     * @return $this
     */
    protected function withAuth($user = null, string $driver = 'api')
    {
        return $this->actingAs($user ?? factory($this->resolveUserModelClass())->create(), $driver);
    }

    /**
     * Returns user model class used to create user instance for authentication.
     *
     * @return string|null
     */
    protected function resolveUserModelClass(): ?string
    {
        return null;
    }

    /**
     * Forces authorization for the current request.
     *
     * @return $this
     */
    protected function requireAuthorization()
    {
        app()->bind('orion.authorizationRequired', function () {
            return true;
        });

        return $this;
    }

    /**
     * Disables authorization for the current request.
     *
     * @return $this
     */
    protected function bypassAuthorization()
    {
        app()->bind('orion.authorizationRequired', function () {
            return false;
        });

        return $this;
    }

    /**
     *
     * @param \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse $response
     */
    protected function assertUnauthorizedResponse($response) : void
    {
        $response->assertStatus(403);
        $response->assertJson(['message' => 'This action is unauthorized.']);
    }
}