<?php

namespace Orion\Concerns;

use Symfony\Component\HttpFoundation\Response;

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
