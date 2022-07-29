<?php

namespace Orion\Specs\Fields;

class ArrayField extends Field
{
    /** @var string $itemType */
    protected $itemType;

    public function of(string $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * @return string
     */
    public function getItemType(): string
    {
        return $this->itemType;
    }
}
