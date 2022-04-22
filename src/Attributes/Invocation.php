<?php


namespace Orion\Attributes;

use Attribute;

/**
 * Class Invocation
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Invocation
{
    /**
     * @param array $input
     */
    public function __construct(
        public array $input,
    ){}
}
