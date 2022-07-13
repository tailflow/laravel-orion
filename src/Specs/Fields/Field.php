<?php

declare(strict_types=1);

namespace Orion\Specs\Fields;

class Field
{
    /** @var bool $required */
    protected $required = true;
    /** @var string|null $description */
    protected $description;

    public function optional(): self
    {
        $this->required = false;

        return $this;
    }

    /**
     * @param string|null $description
     * @return Field
     */
    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
