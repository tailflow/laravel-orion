<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components;

use Orion\Specs\Builders\PropertyBuilder;
use Orion\Specs\Managers\SchemaManager;

abstract class ModelComponentBuilder
{
    /**
     * @var SchemaManager
     */
    protected $schemaManager;
    /**
     * @var PropertyBuilder
     */
    protected $propertyBuilder;

    /**
     * BaseModelComponentBuilder constructor.
     *
     * @param SchemaManager $schemaManager
     * @param PropertyBuilder $propertyBuilder
     */
    public function __construct(SchemaManager $schemaManager, PropertyBuilder $propertyBuilder)
    {
        $this->schemaManager = $schemaManager;
        $this->propertyBuilder = $propertyBuilder;
    }

}
