<?php

namespace Orion\Specs\Fields;

class Field
{
    /** @var bool $required */
    protected $required = true;
    /** @var string|null $description */
    protected $description;
    /** @var array $rules */
    protected $rules;

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

    /**
     * @param array $rules
     * @return Field
     */
    public function rules(array $rules): Field
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
