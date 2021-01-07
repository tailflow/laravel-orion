<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Tests\Fixtures\App\Models\AccessKey;

class AccessKeysController extends Controller
{
    /**
     * @var string|null $model
     */
    protected $model = AccessKey::class;

    protected function keyName(): string
    {
        return 'key';
    }
}