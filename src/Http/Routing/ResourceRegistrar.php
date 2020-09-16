<?php

namespace Orion\Http\Routing;

use Illuminate\Routing\Route;

class ResourceRegistrar extends \Illuminate\Routing\ResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['search', 'batchStore', 'batchUpdate', 'batchDestroy', 'batchRestore', 'index', 'store', 'show', 'update', 'destroy', 'restore'];

    /**
     * Add the search method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceSearch(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getResourceUri($name).'/search';

        $action = $this->getResourceAction($name, $controller, 'index', $options);

        $action['as'] = str_replace('.index', '.search', $action['as']);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the restore method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceRestore(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/restore';

        $action = $this->getResourceAction($name, $controller, 'restore', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the batch store method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceBatchStore(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getResourceUri($name).'/batch';

        $action = $this->getResourceAction($name, $controller, 'batchStore', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the batch update method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceBatchUpdate(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getResourceUri($name).'/batch';

        $action = $this->getResourceAction($name, $controller, 'batchUpdate', $options);

        return $this->router->patch($uri, $action);
    }

    /**
     * Add the batch destroy for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceBatchDestroy(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getResourceUri($name).'/batch';

        $action = $this->getResourceAction($name, $controller, 'batchDestroy', $options);

        return $this->router->delete($uri, $action);
    }

    /**
     * Add the batch restore for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceBatchRestore(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getResourceUri($name).'/batch/restore';

        $action = $this->getResourceAction($name, $controller, 'batchRestore', $options);

        return $this->router->post($uri, $action);
    }
}