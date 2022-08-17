<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

class ModelResourceComponent extends Component
{
    public function toArray(): array
    {
        return array_merge(
            [
                'title' => $this->title,
            ],
            $this->properties
        );
    }
}
