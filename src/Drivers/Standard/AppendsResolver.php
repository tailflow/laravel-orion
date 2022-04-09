<?php

declare(strict_types=1);

namespace Orion\Drivers\Standard;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orion\Http\Requests\Request;

class AppendsResolver implements \Orion\Contracts\AppendsResolver
{
    protected array $alwaysAppends;
    protected array $appends;

    public function __construct(array $alwaysAppends, array $appends)
    {
        $this->alwaysAppends = $alwaysAppends;
        $this->appends = $appends;
    }

    public function requestedAppends(Request $request): array
    {
        $requestedAppendsStr = $request->get('append', '');
        $requestedAppends = explode(',', $requestedAppendsStr);

        $appends = array_unique(array_merge($this->appends, $this->alwaysAppends));

        return array_intersect($appends, $requestedAppends);
    }

    public function appendToEntity(Model $entity, Request $request): Model
    {
        return $entity->append(
            $this->requestedAppends($request)
        );
    }

    /**
     * @param Paginator|Collection $collection
     * @param Request $request
     * @return Paginator|Collection
     */
    public function appendToCollection($collection, Request $request)
    {
        ($collection instanceof Paginator ? $collection->getCollection() : $collection)
            ->transform(function (Model $entity) use ($request) {
                return $this->appendToEntity($entity, $request);
            });

        return $collection;
    }
}
