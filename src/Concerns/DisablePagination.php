<?php

declare(strict_types=1);

namespace Orion\Concerns;

trait DisablePagination
{
    /**
     * @var bool $paginationDisabled
     */
    protected $paginationDisabled = true;
}