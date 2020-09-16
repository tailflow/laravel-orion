<?php

namespace Orion\Http\Routing;

use Illuminate\Routing\Route;

class HasManyRelationResourceRegistrar extends RelationResourceRegistrar
{
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['search', 'batchStore', 'batchUpdate', 'batchDestroy', 'batchRestore', 'associate', 'dissociate', 'index', 'store', 'show', 'update', 'destroy', 'restore'];

    /**
     * Add the associate method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceAssociate(string $name, string $base, string $controller, array $options)
    {
        $uri = $this->getResourceUri($name).'/associate';

        $action = $this->getResourceAction($name, $controller, 'associate', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the dissociate method for a resourceful route.
     *
     * @param string $name
     * @param string $base
     * @param string $controller
     * @param array $options
     * @return Route
     */
    protected function addResourceDissociate(string $name, string $base, string $controller, array $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'?}/dissociate';

        $action = $this->getResourceAction($name, $controller, 'dissociate', $options);

        return $this->router->delete($uri, $action);
    }
}