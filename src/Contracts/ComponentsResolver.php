<?php

declare(strict_types=1);

namespace Orion\Contracts;

use Orion\Repositories\BaseRepository;

interface ComponentsResolver
{
    public function __construct(string $resourceModelClass);

    public function resolveRepositoryClass(): string;

    public function resolveRequestClass(): string;

    public function resolveResourceClass(): string;

    public function resolveCollectionResourceClass(): ?string;

    public function bindRequestClass(string $requestClass): void;
    public function bindPolicyClass(string $policyClass): void;

    public function instantiateRepository(string $repositoryClass): BaseRepository;
}
