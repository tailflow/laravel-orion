<?php

declare(strict_types=1);

namespace Orion\ValueObjects;

use Orion\Contracts\Http\Guards\GuardOptions;

class RegisteredGuard
{
    public string $guardClass;
    public GuardOptions $options;
}
