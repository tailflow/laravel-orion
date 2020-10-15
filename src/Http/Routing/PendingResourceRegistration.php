<?php

namespace Orion\Http\Routing;

use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Arr;

class PendingResourceRegistration extends \Illuminate\Routing\PendingResourceRegistration
{
    /**
     * Create a new pending resource registration instance.
     *
     * @param ResourceRegistrar $registrar
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return void
     */
    public function __construct(ResourceRegistrar $registrar, string $name, string $controller, array $options)
    {
        parent::__construct($registrar, $name, $controller, array_merge([
            'except' => ['restore', 'batchRestore']
        ], $options));
    }

    /**
     * Enables "restore" operation on the resource.
     *
     * @return $this
     */
    public function withSoftDeletes() : PendingResourceRegistration
    {
        $except = Arr::get($this->options, 'except');

        unset($except[array_search('restore', $except, true)]);
        unset($except[array_search('batchRestore', $except, true)]);

        $this->except($except);

        return $this;
    }

    /**
     * Disables batch operations on the resource.
     *
     * @return $this
     */
    public function withoutBatch() : PendingResourceRegistration
    {
        $except = Arr::get($this->options, 'except');

        $except = array_merge($except, ['batchStore', 'batchUpdate', 'batchDestroy', 'batchRestore']);

        $this->except($except);

        return $this;
    }
}