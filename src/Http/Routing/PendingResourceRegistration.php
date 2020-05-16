<?php

namespace Orion\Http\Routing;

use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Arr;

class PendingResourceRegistration extends \Illuminate\Routing\PendingResourceRegistration
{
    /**
     * Create a new pending resource registration instance.
     *
     * @param \Illuminate\Routing\ResourceRegistrar $registrar
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return void
     */
    public function __construct(ResourceRegistrar $registrar, $name, $controller, array $options)
    {
        parent::__construct($registrar, $name, $controller, array_merge([
            'except' => ['restore']
        ], $options));
    }

    /**
     * Enables "restore" endpoint on the resource.
     *
     * @return $this
     */
    public function withSoftDeletes()
    {
        $except = Arr::get($this->options, 'except');
        unset($except[array_search('restore', $except, true)]);

        $this->except($except);

        return $this;
    }
}