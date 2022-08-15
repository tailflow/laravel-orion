<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components;

use Orion\Specs\Builders\PropertyBuilder;
use Orion\Specs\Managers\ResourceManager;
use Orion\Specs\Managers\SchemaManager;

abstract class ModelComponentBuilder
{
    /**
     * @var SchemaManager
     */
    protected $schemaManager;
    /**
     * @var ResourceManager
     */
    protected $resourceManager;
    /**
     * @var PropertyBuilder
     */
    protected $propertyBuilder;

    /**
     * BaseModelComponentBuilder constructor.
     *
     * @param SchemaManager $schemaManager
     * @param ResourceManager $resourceManager
     * @param PropertyBuilder $propertyBuilder
     */
    public function __construct(SchemaManager $schemaManager, ResourceManager $resourceManager, PropertyBuilder $propertyBuilder)
    {
        $this->schemaManager = $schemaManager;
        $this->resourceManager = $resourceManager;
        $this->propertyBuilder = $propertyBuilder;
    }
}
