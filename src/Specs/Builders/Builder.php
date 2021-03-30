<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

class Builder
{
    /** @var InfoBuilder */
    protected $infoBuilder;
    /** @var PathsBuilder  */
    protected $pathsBuilder;
    /** @var ComponentsBuilder  */
    protected $componentsBuilder;
    /** @var SecurityBuilder  */
    protected $securityBuilder;
    /** @var TagsBuilder  */
    protected $tagsBuilder;

    public function __construct(
        InfoBuilder $infoBuilder,
        SecurityBuilder $securityBuilder,
        PathsBuilder $pathsBuilder,
        ComponentsBuilder $componentsBuilder,
        TagsBuilder $tagsBuilder
    ) {
        $this->infoBuilder = $infoBuilder;
        $this->pathsBuilder = $pathsBuilder;
        $this->componentsBuilder = $componentsBuilder;
        $this->securityBuilder = $securityBuilder;
        $this->tagsBuilder = $tagsBuilder;
    }

    public function build(): array
    {
        $info = $this->infoBuilder->build();
        $security = $this->securityBuilder->build();
        $tags = $this->tagsBuilder->build();
        $paths = $this->pathsBuilder->build();
        $components = $this->componentsBuilder->build();
    }
}