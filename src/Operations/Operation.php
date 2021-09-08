<?php

declare(strict_types=1);

namespace Orion\Operations;

use Closure;
use DB;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Pipeline\Pipeline;
use Orion\Contracts\RelationsResolver;
use Throwable;

abstract class Operation extends Pipeline
{
    protected RelationsResolver $relationsResolver;

    protected bool $usesTransaction = false;

    protected string $resourceClass;
    protected ?string $collectionResourceClass;

    protected array $beforeHooks = [];
    protected array $afterHooks = [];

    protected ?Closure $authorizationCallback = null;
    protected ?Closure $performCallback = null;
    protected ?Closure $guardCallback = null;
    protected ?Closure $transformCallback = null;

    public function __construct(RelationsResolver $relationsResolver, Container $container = null)
    {
        parent::__construct($container);

        $this->usesTransaction = config('orion.features.hooks.transactions');
        $this->relationsResolver = $relationsResolver;
    }

    abstract public function perform($payload);

    abstract public function guard($payload);

    abstract public function transform($payload);

    public function useTransaction(bool $usesTransaction): self
    {
        $this->usesTransaction = $usesTransaction;

        return $this;
    }

    public function registerHooks(array $hooks, string $stage): self
    {
        $registrationCallback = $stage === 'before' ? [$this, 'registerBeforeHook'] : [$this, 'registerAfterHook'];

        foreach ($hooks as $hook) {
            $registrationCallback($hook);
        }

        return $this;
    }

    public function registerBeforeHook(callable $hook): self
    {
        $this->beforeHooks[] = $hook;

        return $this;
    }

    public function registerAfterHook(callable $hook): self
    {
        $this->afterHooks[] = $hook;

        return $this;
    }

    public function registerAuthorizationCallback(?callable $callback): self
    {
        $this->authorizationCallback = $callback;

        return $this;
    }

    public function registerPerformCallback(?callable $callback): self
    {
        $this->performCallback = $callback;

        return $this;
    }

    public function registerGuardCallback(?callable $callback): self
    {
        $this->guardCallback = $callback;

        return $this;
    }

    public function registerTransformCallback(?callable $callback): self
    {
        $this->transformCallback = $callback;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $this->through($this->buildPipes());

        $pipeline = $this->prepareDestination($destination);
        $carry = $this->carry();

        foreach (array_reverse($this->pipes()) as $pipe) {
            try {
                $pipeline = $carry($pipeline, $pipe);
            } catch (Throwable $exception) {
                DB::rollBack();
            }

            if ($pipeline($this->passable) instanceof Responsable) {
                break;
            }
        }

        return $pipeline($this->passable);
    }

    protected function buildPipes(): array
    {
        $pipes = [];

        if ($this->authorizationCallback) {
            $pipes[] = $this->authorizationCallback;
        }

        if ($this->usesTransaction) {
            $pipes[] = [DB::class, 'beginTransaction'];
        }

        foreach ($this->beforeHooks as $beforeHook) {
            $pipes[] = $beforeHook;
        }

        $pipes[] = $this->performCallback ?? [$this, 'perform'];

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
