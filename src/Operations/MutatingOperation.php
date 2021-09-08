<?php

declare(strict_types=1);

namespace Orion\Operations;

use DB;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Orion\Contracts\Http\Guards\Guard;
use Orion\ValueObjects\Operations\MutatingOperationPayload;
use Orion\ValueObjects\RegisteredGuard;

abstract class MutatingOperation extends Operation
{
    /** @var callable|null $attributesCallback */
    protected $attributesCallback = null;
    /** @var callable|null $fillCallback */
    protected $fillCallback = null;
    /** @var callable|null $refreshCallback */
    protected $refreshCallback = null;

    abstract public function refresh($payload);

    /**
     * @param MutatingOperationPayload $payload
     * @return MutatingOperationPayload
     * @throws BindingResolutionException
     */
    public function guard($payload): MutatingOperationPayload
    {
        foreach ($this->guards as $registeredGuard) {
            /** @var RegisteredGuard $registeredGuard */
            /** @var Guard $guard */
            $guard = app()->make($registeredGuard->guardClass);

            $payload->entity = $guard->guardEntity($payload->entity, $registeredGuard->options);
        }

        return $payload;
    }

    public function attributes($payload)
    {
        $payload->attributes = $payload->request->all();

        return $payload;
    }

    public function fill($payload)
    {
        $payload->entity->fill(
            Arr::except($payload->attributes, array_keys($payload->entity->getDirty()))
        );

        return $payload;
    }

    public function registerAttributesCallback(?callable $callback): self
    {
        $this->attributesCallback = $callback;

        return $this;
    }

    public function registerFillCallback(?callable $callback): self
    {
        $this->attributesCallback = $callback;

        return $this;
    }

    public function registerRefreshCallback(?callable $callback): self
    {
        $this->refreshCallback = $callback;

        return $this;
    }

    protected function buildPipes(): array
    {
        if ($this->authorizationCallback) {
            $pipes[] = $this->authorizationCallback;
        }

        if ($this->usesTransaction) {
            $pipes[] = [DB::class, 'beginTransaction'];
        }

        foreach ($this->beforeHooks as $beforeHook) {
            $pipes[] = $beforeHook;
        }

        $pipes[] = $this->attributesCallback ?? [$this, 'attributes'];

        $pipes[] = $this->fillCallback ?? [$this, 'fill'];

        $pipes[] = $this->performCallback ?? [$this, 'perform'];

        $pipes[] = $this->refreshCallback ?? [$this, 'refresh'];

        foreach ($this->afterHooks as $afterHook) {
            $pipes[] = $afterHook;
        }

        if ($this->usesTransaction) {
            $pipes[] = [DB::class, 'commit'];
        }

        $pipes[] = $this->guardCallback ?? [$this, 'guard'];
        $pipes[] = $this->transformCallback ?? [$this, 'transform'];

        return $pipes;
    }
}
