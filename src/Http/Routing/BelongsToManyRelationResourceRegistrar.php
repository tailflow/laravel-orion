<?php

namespace Orion\Http\Routing;

use Illuminate\Routing\Route;

class BelongsToManyRelationResourceRegistrar extends RelationResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['search', 'batchStore', 'batchUpdate', 'batchDestroy', 'batchRestore', 'sync', 'toggle', 'updatePivot', 'attach', 'detach', 'index', 'store', 'show', 'update', 'destroy', 'restore'];

    /**
     * Add the sync method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceSync(string $name, string $base, string $controller, array $options)
    {
        $uri = $this->getResourceUri($name).'/sync';

        $action = $this->getResourceAction($name, $controller, 'sync', $options);

        return $this->router->patch($uri, $action);
    }

    /**
     * Add the toggle method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceToggle(string $name, string $base, string $controller, array $options)
    {
        $uri = $this->getResourceUri($name).'/toggle';

        $action = $this->getResourceAction($name, $controller, 'toggle', $options);

        return $this->router->patch($uri, $action);
    }

    /**
     * Add the updatePivot method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceUpdatePivot(string $name, string $base, string $controller, array $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'?}/pivot';

        $action = $this->getResourceAction($name, $controller, 'updatePivot', $options);

        return $this->router->patch($uri, $action);
    }

    /**
     * Add the attach method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceAttach(string $name, string $base, string $controller, array $options)
    {
        $uri = $this->getResourceUri($name).'/attach';

        $action = $this->getResourceAction($name, $controller, 'attach', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the detach method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceDetach(string $name, string $base, string $controller, array $options)
    {
        $uri = $this->getResourceUri($name).'/detach';

        $action = $this->getResourceAction($name, $controller, 'detach', $options);

        return $this->router->delete($uri, $action);
    }
}