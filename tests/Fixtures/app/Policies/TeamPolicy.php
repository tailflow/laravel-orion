<?php


namespace Orion\Tests\Fixtures\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Orion\Tests\Fixtures\App\Models\Team;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of teams.
     *
     * @param $user
     * @return bool
     */
    public function viewAny($user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the team.
     *
     * @param $user
     * @param Team $team
     * @return bool
     */
    public function view($user, Team $team)
    {
        return true;
    }

    /**
     * Determine whether the user can create teams.
     *
     * @param $user
     * @return bool
     */
    public function create($user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the team.
     *
     * @param $user
     * @param Team $post
     * @return bool
     */
    public function update($user, Team $post)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the team.
     *
     * @param $user
     * @param Team $post
     * @return bool
     */
    public function delete($user, Team $post)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the team.
     *
     * @param $user
     * @param Team $post
     * @return bool
     */
    public function restore($user, Team $post)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the team.
     *
     * @param $user
     * @param Team $post
     * @return bool
     */
    public function forceDelete($user, Team $post)
    {
        return true;
    }
}

