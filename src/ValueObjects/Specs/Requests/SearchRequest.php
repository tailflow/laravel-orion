<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs\Requests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Orion\Specs\Builders\Partials\RequestBody\Search\AggregatesBuilder;
use Orion\Specs\Builders\Partials\RequestBody\Search\FiltersBuilder;
use Orion\Specs\Builders\Partials\RequestBody\Search\IncludesBuilder;
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

    /** @var IncludesBuilder */
    protected $includesBuilder;

    /** @var AggregatesBuilder */
    protected $aggregatesBuilder;

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
        $this->includesBuilder = app()->makeWith(IncludesBuilder::class, ['controller' => $controller]);
        $this->aggregatesBuilder = app()->makeWith(AggregatesBuilder::class, ['controller' => $controller]);
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

        if ($includes = $this->includesBuilder->build()) {
            $properties['includes'] = $includes;
        }

        if ($aggregates = $this->aggregatesBuilder->build()) {
            $properties['aggregates'] = $aggregates;
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
