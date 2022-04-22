<?php


namespace Orion\Concerns;

/**
 * Trait EloquentBuilder
 * @package Orion\Concerns
 */
trait EloquentBuilder
{

    /**
     * @return \Orion\Jobs\JobDispatcher|__anonymous@222
     */
    public function builder()
    {
        if (!$this->instance) {
            $this->instance = new class{};
        }
        return $this->instance;
    }
}
