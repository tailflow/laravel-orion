<?php

declare(strict_types=1);

namespace Orion\Operations;

use Closure;
use DB;
use Illuminate\Support\Arr;

abstract class MutatingOperation extends Operation
{
    protected ?Closure $attributesCallback = null;
    protected ?Closure $fillCallback = null;
    protected ?Closure $refreshCallback = null;

    abstract public function refresh($payload);

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
