<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Http\Resources\SupplierCollectionResource;
use Orion\Tests\Fixtures\App\Models\Supplier;

class SuppliersController extends Controller
{
    use DisableAuthorization;

    /**
     * @var string|null $model
     */
    protected static $model = Supplier::class;

    /**
     * @var string $collectionResource
     */
    protected static $collectionResource = SupplierCollectionResource::class;

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy()
    {
        return ['*'];
    }

    /**
     * @return array
     */
    public function filterableBy()
    {
        return ['*'];
    }

    /**
     * The relations that are always included together with a resource.
     *
     * @return array
     */
    protected function alwaysIncludes()
    {
        return ['team'];
    }
}
