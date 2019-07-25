<?php

namespace Orion\Drivers\Standard;


use Orion\Concerns\DriverBuilder;
use Orion\Concerns\EloquentBuilder;
use Illuminate\Support\Manager;

class OrionBuilder extends Manager
{
    use EloquentBuilder, DriverBuilder;

    /**
     * The default manager used.
     *
     * @var string
     */
    protected $defaultManager;

    public function getDefaultDriver()
    {
        return $this->defaultManager;
    }

    /**
     * Set the default manager driver name.
     *
     * @param string $manager
     *
     * @return void
     */
    public function build($manager = 'query')
    {
        return $this->driver($manager);
    }
}

