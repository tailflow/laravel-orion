<?php


namespace Orion\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Invocation
{
    public function __construct(
        public array $input,
    ){}
}
