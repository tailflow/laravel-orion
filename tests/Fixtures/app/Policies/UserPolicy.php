<?php

namespace Orion\Tests\Fixtures\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Orion\Tests\Fixtures\App\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of posts.
     *
     * @param $user
     * @return bool
     */
    public function viewAny($user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the post.
     *
     * @param $authenticatedUser
     * @param User $user
     * @return bool
     */
    public function view($authenticatedUser, User $user)
    {
        return (int) $authenticatedUser->id === $authenticatedUser->id;
    }

    /**
     * Determine whether the user can create posts.
     *
     * @param $user
     * @return bool
     */
    public function create($user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the post.
     *
     * @param $authenticatedUser
     * @param User $user
     * @return bool
     */
    public function update($authenticatedUser, User $user)
    {
        return (int) $user->id === $authenticatedUser->id;
    }

    /**
     * Determine whether the user can delete the post.
     *
     * @param $authenticatedUser
     * @param User $user
     * @return bool
     */
    public function delete($authenticatedUser, User $user)
    {
        return (int) $user->id === $authenticatedUser->id;
    }
}

