<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Operations;

abstract class MutatingOperationPayload extends OperationPayload
{
    public ?array $attributes;
}
