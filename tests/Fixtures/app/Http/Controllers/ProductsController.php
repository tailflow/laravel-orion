<?php

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Http\Resources\ProductResource;
use Orion\Tests\Fixtures\App\Models\Product;

class ProductsController extends Controller
{
    /**
     * @var string|null $model
     */
    protected $model = Product::class;

    /**
    * @var string|null $resource
    */
    protected $resource = ProductResource::class;
}
