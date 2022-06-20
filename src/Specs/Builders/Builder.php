<?php

declare(strict_types=1);

namespace Orion\Specs\Builders;

use Illuminate\Contracts\Container\BindingResolutionException;

class Builder
{
    /** @var InfoBuilder */
    protected $infoBuilder;
    /** @var ServersBuilder */
    protected $serversBuilder;
    /** @var PathsBuilder */
    protected $pathsBuilder;
    /** @var ComponentsBuilder */
    protected $componentsBuilder;
    /** @var SecurityBuilder */
    protected $securityBuilder;
    /** @var TagsBuilder */
    protected $tagsBuilder;

    public function __construct(
        InfoBuilder $infoBuilder,
        ServersBuilder $serversBuilder,
        SecurityBuilder $securityBuilder,
        PathsBuilder $pathsBuilder,
        ComponentsBuilder $componentsBuilder,
        TagsBuilder $tagsBuilder
    ) {
        $this->infoBuilder = $infoBuilder;
        $this->serversBuilder = $serversBuilder;
        $this->pathsBuilder = $pathsBuilder;
        $this->componentsBuilder = $componentsBuilder;
        $this->securityBuilder = $securityBuilder;
        $this->tagsBuilder = $tagsBuilder;
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    public function build(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => $this->infoBuilder->build(),
            'servers' => $this->serversBuilder->build(),
            'security' => $this->securityBuilder->build(),
            'paths' => $this->pathsBuilder->build(),
            'components' => $this->componentsBuilder->build(),
            'tags' => $this->tagsBuilder->build(),
        ];
    }
}
