<?php

namespace Orion\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Orion\Concerns\BuildsResponses;
use Orion\Concerns\HandlesAuthorization;
use Orion\Concerns\HandlesTransactions;
use Orion\Concerns\InteractsWithBatchResources;
use Orion\Concerns\InteractsWithHooks;
use Orion\Concerns\InteractsWithSoftDeletes;
use Orion\Contracts\ComponentsResolver;
use Orion\Contracts\Paginator;
use Orion\Contracts\ParamsValidator;
use Orion\Contracts\QueryBuilder;
use Orion\Contracts\RelationsResolver;
use Orion\Contracts\SearchBuilder;
use Orion\Exceptions\BindingException;
use Orion\Http\Requests\Request;

abstract class BaseController extends \Illuminate\Routing\Controller
{
    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests,
        HandlesAuthorization,
        InteractsWithHooks,
        InteractsWithSoftDeletes,
        InteractsWithBatchResources,
        BuildsResponses,
        HandlesTransactions;

    /**
     * @var string $model
     */
    protected $model;

    /**
     * @var string $request
     */
    protected $request;

    /**
     * @var string $resource
     */
    protected $resource;

    /**
     * @var string|null $collectionResource
     */
    protected $collectionResource = null;

    /**
     * @var ComponentsResolver $componentsResolver
     */
    protected $componentsResolver;

    /**
     * @var ParamsValidator $paramsValidator
     */
    protected $paramsValidator;

    /**
     * @var RelationsResolver $relationsResolver
     */
    protected $relationsResolver;

    /**
     * @var Paginator $paginator
     */
    protected $paginator;

    /**
     * @var SearchBuilder $searchBuilder
     */
    protected $searchBuilder;

    /**
     * @var QueryBuilder $queryBuilder
     */
    protected $queryBuilder;

    /**
     * @var boolean $applyAllQueryToIndex
     */
    protected $applyAllQueryToIndex;

    /**
     * Controller constructor.
     *
     * @throws BindingException
     */
    public function __construct()
    {
        if (!$this->model) {
            throw new BindingException('Model is not defined for ' . static::class);
        }

        $this->componentsResolver = App::makeWith(
            ComponentsResolver::class,
            [
                'resourceModelClass' => $this->resolveResourceModelClass(),
            ]
        );
        $this->paramsValidator = App::makeWith(
            ParamsValidator::class,
            [
                'exposedScopes' => $this->exposedScopes(),
                'filterableBy' => $this->filterableBy(),
                'sortableBy' => $this->sortableBy(),
            ]
        );
        $this->relationsResolver = App::makeWith(
            RelationsResolver::class,
            [
                'includableRelations' => $this->includes(),
                'alwaysIncludedRelations' => $this->alwaysIncludes(),
            ]
        );
        $this->paginator = App::makeWith(
            Paginator::class,
            [
                'defaultLimit' => $this->limit(),
            ]
        );
        $this->searchBuilder = App::makeWith(
            SearchBuilder::class,
            [
                'searchableBy' => $this->searchableBy(),
            ]
        );
        $this->queryBuilder = App::makeWith(
            QueryBuilder::class,
            [
                'resourceModelClass' => $this->getModel(),
                'paramsValidator' => $this->paramsValidator,
                'relationsResolver' => $this->relationsResolver,
                'searchBuilder' => $this->searchBuilder,
                'intermediateMode' => $this instanceof RelationController,
                'applyQueryToIndex' => $this->applyAllQueryToIndex,
            ]
        );

        $this->resolveComponents();
        $this->bindComponents();
    }

    /**
     * Retrieves model related to resource.
     *
     * @return string
     */
    abstract public function resolveResourceModelClass(): string;

    /**
     * Retrieves the query builder used to query the end-resource.
     *
     * @return QueryBuilder
     */
    abstract public function getResourceQueryBuilder(): QueryBuilder;

    /**
     * The list of available query scopes.
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [];
    }

    /**
     * The attributes from filterableBy method that have "scoped"
     * filter options included in the response.
     *
     * @return array
     */
    public function scopedFilters(): array
    {
        return [];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [];
    }

    /**
     * The relations that are allowed to be included together with a resource.
     *
     * @return array
     */
    public function includes(): array
    {
        return [];
    }

    /**
     * The relations that are always included together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [];
    }

    /**
     * Default pagination limit.
     *
     * @return int
     */
    public function limit(): int
    {
        return 15;
    }

    /**
     * The attributes that are used for searching.
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function setModel(string $modelClass): self
    {
        $this->model = $modelClass;

        return $this;
    }

    /**
     * Resolves request, resource and collection resource classes.
     */
    protected function resolveComponents(): void
    {
        if (!$this->request) {
            $this->setRequest($this->componentsResolver->resolveRequestClass());
        }

        if (!$this->resource) {
            $this->setResource($this->componentsResolver->resolveResourceClass());
        }

        if (!$this->collectionResource) {
            $this->setCollectionResource($this->componentsResolver->resolveCollectionResourceClass());
        }
    }

    /**
     * Binds resolved request class to the container.
     */
    protected function bindComponents(): void
    {
        $this->componentsResolver->bindRequestClass($this->getRequest());
    }

    /**
     * @return string
     */
    public function getRequest(): string
    {
        return $this->request;
    }

    /**
     * @param string $requestClass
     * @return $this
     */
    public function setRequest(string $requestClass): self
    {
        $this->request = $requestClass;

        return $this;
    }

    /**
     * Authorize a given action for the current user.
     *
     * @param string $ability
     * @param array $arguments
     * @return Response
     * @throws AuthorizationException
     * @throws BindingResolutionException
     */
    public function authorize(string $ability, $arguments = [])
    {
        if (!$this->authorizationRequired()) {
            return $this->authorized();
        }

        $user = $this->resolveUser();

        return $this->authorizeForUser($user, $ability, $arguments);
    }

    /**
     * Retrieves currently authenticated user based on the guard.
     *
     * @return Authenticatable|null
     */
    public function resolveUser()
    {
        return Auth::guard(config('orion.auth.guard', 'api'))->user();
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @param string $resourceClass
     * @return $this
     */
    public function setResource(string $resourceClass): self
    {
        $this->resource = $resourceClass;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCollectionResource(): ?string
    {
        return $this->collectionResource;
    }

    /**
     * @param string|null $collectionResourceClass
     * @return $this
     */
    public function setCollectionResource(?string $collectionResourceClass): self
    {
        $this->collectionResource = $collectionResourceClass;

        return $this;
    }

    /**
     * @return ComponentsResolver
     */
    public function getComponentsResolver(): ComponentsResolver
    {
        return $this->componentsResolver;
    }

    /**
     * @param ComponentsResolver $componentsResolver
     * @return $this
     */
    public function setComponentsResolver(ComponentsResolver $componentsResolver): self
    {
        $this->componentsResolver = $componentsResolver;

        return $this;
    }

    /**
     * @return ParamsValidator
     */
    public function getParamsValidator(): ParamsValidator
    {
        return $this->paramsValidator;
    }

    /**
     * @param ParamsValidator $paramsValidator
     * @return $this
     */
    public function setParamsValidator(ParamsValidator $paramsValidator): self
    {
        $this->paramsValidator = $paramsValidator;

        return $this;
    }

    /**
     * @return RelationsResolver
     */
    public function getRelationsResolver(): RelationsResolver
    {
        return $this->relationsResolver;
    }

    /**
     * @param RelationsResolver $relationsResolver
     * @return $this
     */
    public function setRelationsResolver(RelationsResolver $relationsResolver): self
    {
        $this->relationsResolver = $relationsResolver;

        return $this;
    }

    /**
     * @return Paginator
     */
    public function getPaginator(): Paginator
    {
        return $this->paginator;
    }

    /**
     * @param Paginator $paginator
     * @return $this
     */
    public function setPaginator(Paginator $paginator): self
    {
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * @return SearchBuilder
     */
    public function getSearchBuilder(): SearchBuilder
    {
        return $this->searchBuilder;
    }

    /**
     * @param SearchBuilder $searchBuilder
     * @return $this
     */
    public function setSearchBuilder(SearchBuilder $searchBuilder): self
    {
        $this->searchBuilder = $searchBuilder;

        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Creates new Eloquent query builder of the model.
     *
     * @return Builder
     */
    public function newModelQuery(): Builder
    {
        return $this->getModel()::query();
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap(): array
    {
        return [
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }

    /**
     * A qualified name of the field used to fetch a resource from the database.
     *
     * @return string
     */
    protected function resolveQualifiedKeyName(): string
    {
        $resourceModelClass = $this->resolveResourceModelClass();
        return (new $resourceModelClass)->qualifyColumn($this->keyName());
    }

    protected function resolveQualifiedFieldName(string $field): string
    {
        $resourceModelClass = $this->resolveResourceModelClass();
        return (new $resourceModelClass)->qualifyColumn($field);
    }

    protected function resolveQualifiedRelationFieldName(string $relation, string $field): string
    {
        $resourceModelClass = $this->resolveResourceModelClass();
        return (new $resourceModelClass)::{$relation}()->qualifyColumn($field);
    }

    /**
     * The name of the field used to fetch a resource from the database.
     *
     * @return string
     */
    protected function keyName(): string
    {
        $resourceModelClass = $this->resolveResourceModelClass();
        return (new $resourceModelClass)->getKeyName();
    }

    /**
     * Determine whether pagination is enabled or not.
     *
     * @param Request $request
     * @param int $paginationLimit
     * @return bool
     * @throws BindingResolutionException
     */
    protected function shouldPaginate(Request $request, int $paginationLimit): bool
    {
        if (app()->bound('orion.paginationEnabled')) {
            return app()->make('orion.paginationEnabled');
        }

        return !property_exists($this, 'paginationDisabled');
    }
}
