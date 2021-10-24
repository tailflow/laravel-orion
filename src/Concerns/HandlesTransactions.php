<?php

namespace Orion\Concerns;

use Illuminate\Support\Facades\DB;

trait HandlesTransactions
{
    /**
     * Flag to enable or disable transactions
     *
     * @var boolean
     */
    protected $transactionsEnabled = false;

    /**
     * Start database transaction
     *
     * @return void
     */
    protected function startTransaction(): void
    {
        if ($this->transactionsEnabled !== true) {
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
        if ($this->transactionsEnabled !== true) {
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
        if ($this->transactionsEnabled !== true) {
            return;
        }

        DB::rollBack();
    }

    /**
     * Rollback changes made to database and finish
     * database transaction and finally raise an exception
     *
     * @param \Exception $exception
     * @return void
     *
     * @throws Exception
     */
    protected function rollbackTransactionAndRaise(\Exception $exception): void
    {
        $this->rollbackTransaction();

        throw $exception;
    }
}
