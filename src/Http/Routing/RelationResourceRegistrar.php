<?php

namespace Orion\Http\Routing;

use Illuminate\Routing\Route;

class RelationResourceRegistrar extends ResourceRegistrar
{
    /**
     * Add the update method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceUpdate($name, $base, $controller, $options): Route
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'?}';

        $action = $this->getResourceAction($name, $controller, 'update', $options);

        return $this->router->match(['PUT', 'PATCH'], $uri, $action);
    }

    /**
     * Add the show method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceShow($name, $base, $controller, $options) : Route
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'?}';

        $action = $this->getResourceAction($name, $controller, 'show', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the destroy method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceDestroy($name, $base, $controller, $options) : Route
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'?}';

        $action = $this->getResourceAction($name, $controller, 'destroy', $options);

        return $this->router->delete($uri, $action);
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
    protected function addResourceRestore(string $name, string $base, string $controller, array $options) : Route
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'?}/restore';

        $action = $this->getResourceAction($name, $controller, 'restore', $options);

        return $this->router->post($uri, $action);
    }
}