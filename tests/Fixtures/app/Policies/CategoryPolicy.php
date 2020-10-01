<?php

namespace Orion\Tests\Fixtures\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Orion\Tests\Fixtures\App\Models\Category;

class CategoryPolicy
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
     * @param $user
     * @param Category $category
     * @return bool
     */
    public function view($user, Category $category)
    {
        return true;
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
     * @param $user
     * @param Category $category
     * @return bool
     */
    public function update($user, Category $category)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the post.
     *
     * @param $user
     * @param Category $category
     * @return bool
     */
    public function delete($user, Category $category)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the post.
     *
     * @param $user
     * @param Category $category
     * @return bool
     */
    public function restore($user, Category $category)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the post.
     *
     * @param $user
     * @param Category $category
     * @return bool
     */
    public function forceDelete($user, Category $category)
    {
        return true;
    }
}