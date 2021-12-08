<?php


namespace Orion\Concerns;

/**
 * Trait EloquentBuilder
 * @package Orion\Concerns
 */
trait EloquentBuilder
{

    public function builder()
    {
        if (!$this->instance) {
            $this->instance = new class{};
        }
        return $this->instance;
    }
}
