<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

class SecuritySchemesComponent extends Component
{
    /** @var array */
    public $schemes;

    public function toArray(): array
    {
        return $this->schemes;
    }
}
