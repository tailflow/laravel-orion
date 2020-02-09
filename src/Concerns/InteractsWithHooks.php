<?php

namespace Orion\Concerns;

use Illuminate\Http\Response;

trait InteractsWithHooks
{
    /**
     * Determine whether hook returns a response or not.
     *
     * @param mixed $hookResult
     * @return bool
     */
    public function hookResponds($hookResult)
    {
        return $hookResult instanceof Response;
    }
}
