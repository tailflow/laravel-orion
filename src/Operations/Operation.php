<?php

declare(strict_types=1);

namespace Orion\Operations;

use Closure;
use DB;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Pipeline\Pipeline;
use Throwable;

abstract class Operation extends Pipeline
{
    protected bool $usesTransaction = false;

    protected array $beforeHooks = [];
    protected array $afterHooks = [];

    protected ?Closure $authorizeCallback = null;
    protected ?Closure $performCallback = null;
    protected ?Closure $refreshCallback = null;
    protected ?Closure $guardCallback = null;
    protected ?Closure $transformCallback = null;

    public function __construct(Container $container = null)
    {
        parent::__construct($container);

        $this->usesTransaction = config('orion.features.hooks.transactions');
    }

    abstract public function perform();
    abstract public function guard();
    abstract public function transform();

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
        $this->authorizeCallback = $callback;

        return $this;
    }

    public function registerPerformCallback(?callable $callback): self
    {
        $this->performCallback = $callback;

        return $this;
    }

    public function registerRefreshCallback(?callable $callback): self
    {
        $this->refreshCallback = $callback;

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

        if ($this->authorizeCallback) {
            $pipes[] = $this->authorizeCallback;
        }

        if ($this->usesTransaction) {
            $pipes[] = [DB::class, 'beginTransaction'];
        }

        foreach ($this->beforeHooks as $beforeHook) {
            $pipes[] = $beforeHook;
        }

        $pipes[] = $this->performCallback ?? [$this, 'perform'];

        if ($this->refreshCallback) {
            $pipes[] = $this->refreshCallback;
        }

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
