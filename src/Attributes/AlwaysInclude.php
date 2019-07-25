<?php


namespace Orion\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AlwaysInclude
{
    public function __construct(
        public array $input,
    ){}
}
