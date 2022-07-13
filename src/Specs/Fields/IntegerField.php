<?php

declare(strict_types=1);

namespace Orion\Specs\Fields;

class IntegerField extends Field
{
    /** @var int|null $min */
    protected $min;
    /** @var int|null $max */
    protected $max;

    /**
     * @param int|null $min
     * @return self
     */
    public function min(?int $min): self
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @param int|null $max
     * @return self
     */
    public function max(?int $max): self
    {
        $this->max = $max;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMin(): ?int
    {
        return $this->min;
    }

    /**
     * @return int|null
     */
    public function getMax(): ?int
    {
        return $this->max;
    }
}
