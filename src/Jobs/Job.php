<?php

namespace Orion\Jobs;

use Orion\Concerns\HandlesAssociation;
use Orion\Concerns\HandlesParameters;

abstract class Job
{
    use HandlesAssociation, HandlesParameters;

    /**
     * Stores params fields
     *
     * @var array
     */
    protected $params = [];

    /**
     * Construct
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
    }
}
