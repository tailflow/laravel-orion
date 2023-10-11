<?php

declare(strict_types=1);

namespace Orion\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MaxNestedDepthExceededException extends HttpException
{

}
