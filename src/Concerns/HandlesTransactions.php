<?php

namespace Orion\Concerns;

use Exception;
use Illuminate\Support\Facades\DB;

trait HandlesTransactions
{
    /**
     * Start database transaction
     *
     * @return void
     */
    protected function startTransaction(): void
    {
        if ($this->transactionsAreEnabled() !== true) {
            return;
        }

        DB::beginTransaction();
    }

    /**
     * Commit changes to database and finish database
     * transaction
     *
     * @return void
     */
    protected function commitTransaction(): void
    {
        if ($this->transactionsAreEnabled() !== true) {
            return;
        }

        DB::commit();
    }

    /**
     * Rollback changes made to database and finish
     * database transaction
     *
     * @return void
     */
    protected function rollbackTransaction(): void
    {
        if ($this->transactionsAreEnabled() !== true) {
            return;
        }

        DB::rollBack();
    }

    /**
     * Rollback changes made to database and finish
     * database transaction and finally raise an exception
     *
     * @param Exception $exception
     * @return void
     *
     * @throws Exception
     */
    protected function rollbackTransactionAndRaise(Exception $exception): void
    {
        $this->rollbackTransaction();

        throw $exception;
    }

    /**
     * Return configuration value
     *
     * @return boolean
     */
    protected function transactionsAreEnabled(): bool
    {
        return (bool)config('orion.transactions.enabled', false);
    }
}
