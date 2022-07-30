<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

class ModelResourceComponent extends Component
{
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type,
            'properties' => $this->properties,
        ];
    }
}
