<?php


namespace Orion\Attributes;

use Attribute;

/**
 * Class AlwaysInclude
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AlwaysInclude
{
    /**
     * @param array $input
     */
    public function __construct(
        public array $input,
    ){}
}
