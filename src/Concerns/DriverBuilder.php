<?php


namespace Orion\Concerns;


use Orion\Facades\JobResolver;
use Orion\Facades\QueryBuilder;

/**
 * Trait DriverBuilder
 *
 * @package App\Traits
 */
trait DriverBuilder
{
    /**
     * @return mixed
     */
    public function createJobDriver()
    {
        return JobResolver::builder();
    }

    /**
     * @return mixed
     */
    public function createQueryDriver()
    {
        return QueryBuilder::builder();
    }
}
