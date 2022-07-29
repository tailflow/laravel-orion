<?php

namespace Orion\Http\Responses;

abstract class Response
{
    /**
     * Definition of request fields, their types, and states.
     *
     * @return array
     */
    abstract public function getSchema(): array;
}
