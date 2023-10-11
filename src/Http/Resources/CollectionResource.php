<?php

declare(strict_types=1);

namespace Orion\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Orion\Concerns\ExtendsResources;

class CollectionResource extends ResourceCollection
{
    use ExtendsResources;
}
