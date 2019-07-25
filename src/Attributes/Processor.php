<?php


namespace Orion\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Processor
{
    public function __construct(
        public array $input,
    ){}
}
