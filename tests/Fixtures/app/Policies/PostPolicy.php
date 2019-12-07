<?php


namespace Orion\Tests\Fixtures\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Orion\Tests\Fixtures\App\Models\Post;

class PostPolicy
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
     * @param \Orion\Tests\Fixtures\App\Models\Post $post
     * @return bool
     */
    public function view($user, Post $post)
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
     * @param \Orion\Tests\Fixtures\App\Models\Post $post
     * @return bool
     */
    public function update($user, Post $post)
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the post.
     *
     * @param $user
     * @param \Orion\Tests\Fixtures\App\Models\Post $post
     * @return bool
     */
    public function delete($user, Post $post)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the post.
     *
     * @param $user
     * @param \Orion\Tests\Fixtures\App\Models\Post $post
     * @return bool
     */
    public function restore($user, Post $post)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the post.
     *
     * @param $user
     * @param \Orion\Tests\Fixtures\App\Models\Post $post
     * @return bool
     */
    public function forceDelete($user, Post $post)
    {
        return true;
    }
}

