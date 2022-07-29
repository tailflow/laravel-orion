<?php

namespace Orion\Specs\Fields;

class ObjectField extends Field
{
    /** @var array<Field> $fields */
    protected $fields;

    /**
     * @param array $fields
     * @return ObjectField
     */
    public function fields(array $fields): ObjectField
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
