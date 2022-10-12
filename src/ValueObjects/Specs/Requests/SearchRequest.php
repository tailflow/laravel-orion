<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\Partials\RequestBody\Search\AggregateBuilder;
use Orion\Specs\Builders\Partials\RequestBody\Search\FiltersBuilder;
use Orion\Specs\Builders\Partials\RequestBody\Search\IncludeBuilder;
use Orion\Specs\Builders\Partials\RequestBody\Search\ScopesBuilder;
use Orion\Specs\Builders\Partials\RequestBody\Search\SearchBuilder;
use Orion\Specs\Builders\Partials\RequestBody\Search\SortBuilder;
use Orion\ValueObjects\Specs\Request;

class SearchRequest extends Request
{
    /** @var ScopesBuilder */
    protected $scopesBuilder;

    /** @var FiltersBuilder */
    protected $filtersBuilder;

    /** @var SearchBuilder */
    protected $searchBuilder;

    /** @var SortBuilder */
    protected $sortBuilder;

    /** @var IncludeBuilder */
    protected $includeBuilder;

    /** @var AggregateBuilder */
    protected $aggregateBuilder;

    /**
     * SearchRequest constructor.
     *
     * @param string $controller
     * @throws BindingResolutionException
     */
    public function __construct(string $controller)
    {
        $this->scopesBuilder = app()->makeWith(ScopesBuilder::class, ['controller' => $controller]);
        $this->filtersBuilder = app()->makeWith(FiltersBuilder::class, ['controller' => $controller]);
        $this->searchBuilder = app()->makeWith(SearchBuilder::class, ['controller' => $controller]);
        $this->sortBuilder = app()->makeWith(SortBuilder::class, ['controller' => $controller]);
        $this->includeBuilder = app()->makeWith(IncludeBuilder::class, ['controller' => $controller]);
        $this->aggregateBuilder = app()->makeWith(AggregateBuilder::class, ['controller' => $controller]);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $properties = [];

        if ($scopes = $this->scopesBuilder->build()) {
            $properties['scopes'] = $scopes;
        }

        if ($filters = $this->filtersBuilder->build()) {
            $properties['filters'] = $filters;
        }

        if ($search = $this->searchBuilder->build()) {
            $properties['search'] = $search;
        }

        if ($sort = $this->sortBuilder->build()) {
            $properties['sort'] = $sort;
        }

        if ($include = $this->includeBuilder->build()) {
            $properties['include'] = $include;
        }

        if ($aggregate = $this->aggregateBuilder->build()) {
            $properties['aggregate'] = $aggregate;
        }

        return array_merge(
            parent::toArray(),
            [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $properties
                        ],
                    ],
                ],
            ]
        );
    }
}
