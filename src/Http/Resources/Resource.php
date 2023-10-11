<?php

declare(strict_types=1);

namespace Orion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Orion\Concerns\ExtendsResources;

class Resource extends JsonResource
{
    use ExtendsResources;
}
