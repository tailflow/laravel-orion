<?php

declare(strict_types=1);

namespace Orion\Concerns;

trait DisableAuthorization
{
    protected bool $authorizationDisabled = true;
}
