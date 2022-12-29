<?php

namespace Orion\Tests\Feature;

use Mockery;
use Orion\Contracts\ComponentsResolver;
use Orion\Testing\InteractsWithAuthorization;
use Orion\Testing\InteractsWithJsonFields;
use Orion\Testing\InteractsWithResources;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithResources, InteractsWithJsonFields, InteractsWithAuthorization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withAuth();
    }

    protected function resolveUserModelClass(): ?string
    {
        return User::class;
    }

    protected function noSupportForJsonDbOperations(): bool
    {
        if (config('database.default') === 'sqlite') {
            return true;
        }

        return config('database.default') === 'testing' &&
            config('database.connections.testing.driver') === 'sqlite';
    }

    protected function useRequest(string $requestClass): self
    {
        app()->bind(
            ComponentsResolver::class,
            function () use ($requestClass) {
                $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)
                    ->makePartial();
                $componentsResolverMock->shouldReceive('resolveRequestClass')
                    ->zeroOrMoreTimes()->andReturn($requestClass);

                return $componentsResolverMock;
            }
        );

        return $this;
    }

    protected function useResource(string $resourceClass): self
    {
        app()->bind(
            ComponentsResolver::class,
            function () use ($resourceClass) {
                $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)
                    ->makePartial();
                $componentsResolverMock->shouldReceive('resolveResourceClass')
                    ->zeroOrMoreTimes()->andReturn($resourceClass);

                return $componentsResolverMock;
            }
        );

        return $this;
    }

    protected function useCollectionResource(string $collectionResourceClass): self
    {
        app()->bind(
            ComponentsResolver::class,
            function () use ($collectionResourceClass) {
                $componentsResolverMock = Mockery::mock(\Orion\Drivers\Standard\ComponentsResolver::class)
                    ->makePartial();
                $componentsResolverMock->shouldReceive('resolveCollectionResourceClass')
                    ->zeroOrMoreTimes()->andReturn($collectionResourceClass);

                return $componentsResolverMock;
            }
        );

        return $this;
    }
}
