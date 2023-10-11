<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Http\Controllers;

use Orion\Http\Controllers\RelationController;
use Orion\Tests\Fixtures\App\Models\AccessKey;

class AccessKeyAccessKeyScopesController extends RelationController
{
    public function model(): string
    {
        return AccessKey::class;
    }

    public function relation(): string
    {
        return 'scopes';
    }

    protected function parentKeyName(): string
    {
        return 'key';
    }

    protected function keyName(): string
    {
        return 'scope';
    }
}
