<?php

namespace Laralord\Orion\Http\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Laralord\Orion\Traits\BuildsQuery;

abstract class BaseController extends \Illuminate\Routing\Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, BuildsQuery;

    /**
     * @var string|null $model
     */
    protected static $model = null;

    /**
     * @var string $resource
     */
    protected static $resource = null;

    /**
     * @var string $collectionResource
     */
    protected static $collectionResource = null;

    /**
     * Controller constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        if (!static::$model) {
            throw new Exception('Model is not specified for '.__CLASS__);
        }
        if (!static::$resource) {
            $this->resolveResource();
        }
        if (!static::$collectionResource) {
            $this->resolveCollectionResource();
        }
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    protected function sortableBy()
    {
        return [];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    protected function filterableBy()
    {
        return [];
    }

    /**
     * The attributes that are used for searching.
     *
     * @return array
     */
    protected function searchableBy()
    {
        return [];
    }

    /**
     * The relations that are allowed to be included together with a resource.
     *
     * @return array
     */
    protected function includes()
    {
        return [];
    }

    /**
     * The relations that are always included together with a resource.
     *
     * @return array
     */
    protected function alwaysIncludes()
    {
        return [];
    }

    /**
     * Determine whether authorization is required or not to perform the action.
     *
     * @return bool
     */
    protected function authorizationRequired()
    {
        return property_exists($this, 'authorizationDisabled');
    }

    /**
     * Determine whether hook returns a response or not.
     *
     * @param mixed $hookResult
     * @return bool
     */
    protected function hookResponds($hookResult)
    {
        return $hookResult instanceof Response;
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'index' => 'list',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }

    /**
     * Guesses resource class based on the resource model.
     */
    protected function resolveResource()
    {
        $resourceClassName = 'App\\Http\\Resources\\'.class_basename($this->getResourceModel()).'Resource';
        if (class_exists($resourceClassName)) {
            static::$resource = $resourceClassName;
        } else {
            static::$resource = JsonResource::class;
        }
    }

    /**
     * Guesses collection resource class based on the resource model.
     */
    protected function resolveCollectionResource()
    {
        $collectionResourceClassName = 'App\\Http\\Resources\\'.class_basename($this->getResourceModel()).'CollectionResource';
        if (class_exists($collectionResourceClassName)) {
            static::$collectionResource = $collectionResourceClassName;
        }
    }

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    abstract protected function getResourceModel();
}