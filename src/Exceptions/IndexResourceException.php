<?php


namespace  Orion\Exceptions;

use Exception;
use Throwable;

class IndexResourceException  extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        return true;
    }
}
