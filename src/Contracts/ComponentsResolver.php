<?php

namespace Orion\Contracts;

interface ComponentsResolver
{
    public function __construct(string $resourceModelClass);

    public function resolveRequestClass(): string;

    public function resolveResourceClass(): string;

    public function resolveCollectionResourceClass(): ?string;

    public function bindRequestClass(string $requestClass): void;
}
