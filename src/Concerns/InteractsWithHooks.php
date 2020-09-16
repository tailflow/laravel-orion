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
    protected function hookResponds($hookResult) : bool
    {
        return $hookResult instanceof Response;
    }
}
