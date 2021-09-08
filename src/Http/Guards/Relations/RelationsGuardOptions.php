<?php

declare(strict_types=1);

namespace Orion\Http\Guards\Relations;

use Orion\Contracts\Http\Guards\GuardOptions;

class RelationsGuardOptions implements GuardOptions
{
    public array $requestedRelations = [];

    public ?string $parentRelation = null;
    public bool $normalized = false;
}
