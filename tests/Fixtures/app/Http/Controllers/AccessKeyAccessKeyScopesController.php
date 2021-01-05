<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\AccessKey;

class AccessKeyAccessKeyScopesController extends RelationController
{
    protected $model = AccessKey::class;

    protected $relation = 'scopes';

    protected function parentKeyName(): string
    {
        return 'key';
    }

    protected function keyName(): string
    {
        return 'scope';
    }
}