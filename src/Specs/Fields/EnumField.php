<?php

declare(strict_types=1);

namespace Orion\Specs\Fields;

class EnumField extends Field
{
    /** @var array<mixed> $values */
    protected $values;
    /** @var string|null */
    protected $enum;

    /**
     * @param array $values
     * @return EnumField
     */
    public function values(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @param string|null $enum
     * @return EnumField
     */
    public function enum(?string $enum): self
    {
        $this->enum = $enum;

        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return string|null
     */
    public function getEnum(): ?string
    {
        return $this->enum;
    }
}
