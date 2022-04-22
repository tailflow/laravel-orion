<?php


namespace Orion\Attributes;

use Attribute;

/**
 * Class Processor
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Processor
{
    /**
     * @param array $input
     */
    public function __construct(
        public array $input,
    ){}
}
