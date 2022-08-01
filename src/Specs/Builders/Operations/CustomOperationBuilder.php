<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Operations;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Orion\Specs\Builders\OperationBuilder;
use Orion\ValueObjects\Specs\Operation;
use Orion\ValueObjects\Specs\Request;
use Orion\ValueObjects\Specs\Requests\CustomRequest;
use Orion\ValueObjects\Specs\Responses\Success\CustomResponse;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

class CustomOperationBuilder extends OperationBuilder
{
    /**
     * @return Operation
     * @throws BindingResolutionException
     */
    public function build(): Operation
    {
        $operation = $this->makeBaseOperation();
        $operation->summary = "{$this->route->getName()}"; //TODO: resolve name from the dot notation

        return $operation;
    }

    /**
     * @return Request|null
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function request(): ?Request
    {
        return app()->make(CustomRequest::class, [
            'requestClass' => $this->resolveRequestClass(),
        ]);
    }

    /**
     * @return CustomResponse[]
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function responses(): array
    {
        return app()->make(CustomResponse::class, [
            'responseClass' => $this->resolveResponseClass(),
        ]);
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    protected function resolveRequestClass(): string
    {
        $methodReflector = $this->getMethodReflector();

        $parameters = $methodReflector->getParameters();

        foreach ($parameters as $parameter) {
            /** @var ReflectionParameter $parameter */
            if (!$type = $parameter->getType()) {
                continue;
            }

            $typeReflector = new ReflectionClass($type->getName());

            if (!$typeParentClassReflector = $typeReflector->getParentClass()) {
                continue;
            }

            if ($typeParentClassReflector->getName() === \Orion\Http\Requests\Request::class) {
                return $type->getName();
            }
        }

        return \Illuminate\Http\Request::class;
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    protected function resolveResponseClass(): string
    {
        $methodReflector = $this->getMethodReflector();

        return $methodReflector->getReturnType() ? $methodReflector->getReturnType()->getName() : JsonResponse::class;
    }

    /**
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected function getMethodReflector(): ReflectionMethod
    {
        $method = $this->route->getActionMethod();

        $controllerReflector = new ReflectionClass($this->route->getController());

        return $controllerReflector->getMethod($method);
    }
}
