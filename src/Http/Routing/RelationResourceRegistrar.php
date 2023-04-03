<?php

namespace Orion\Http\Routing;

use Illuminate\Routing\Route;

class RelationResourceRegistrar extends ResourceRegistrar
{
    /**
     * Add the index method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceIndex($name, $base, $controller, $options): Route
    {
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base);

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'index', $options);

        return $this->router->get($uri, $action);
    }

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
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base).'/search';

        $action = $this->getResourceAction($name, $controller, 'search', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the store method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array  $options
     * @return Route
     */
    protected function addResourceStore($name, $base, $controller, $options): Route
    {
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base);

        unset($options['missing']);

        $action = $this->getResourceAction($name, $controller, 'store', $options);

        return $this->router->post($uri, $action);
    }

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
        $uri = $this->getNestedResourceUriWithNestedParameter($name, $base);

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
    protected function addResourceShow($name, $base, $controller, $options): Route
    {
        $uri = $this->getNestedResourceUriWithNestedParameter($name, $base);

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
    protected function addResourceDestroy($name, $base, $controller, $options): Route
    {
        $uri = $this->getNestedResourceUriWithNestedParameter($name, $base);

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
    protected function addResourceRestore(string $name, string $base, string $controller, array $options): Route
    {
        $uri = $this->getNestedResourceUriWithNestedParameter($name, $base).'/restore';

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
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base).'/batch';

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
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base).'/batch';

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
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base).'/batch';

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
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base).'/batch/restore';

        $action = $this->getResourceAction($name, $controller, 'batchRestore', $options);

        return $this->router->post($uri, $action);
    }

    protected function getNestedResourceUriWithNestedParameter(string $name, string $base): string
    {
        $uri = $this->getNestedResourceUriWithoutNestedParameter($name, $base);
        $uri .= "/{".$base."?}";

        return $uri;
    }

    protected function getNestedResourceUriWithoutNestedParameter(string $name, string $base): string
    {
        $uri = $this->getNestedResourceUri(explode('.', $name));

        return rtrim(rtrim($uri, "\{$base\}"), '/');
    }
}
