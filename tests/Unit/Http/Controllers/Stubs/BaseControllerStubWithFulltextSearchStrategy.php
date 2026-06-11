<?php

namespace Orion\Tests\Unit\Http\Controllers\Stubs;

class BaseControllerStubWithFulltextSearchStrategy extends BaseControllerStubWithWhitelistedFieldsAndRelations
{
    protected $searchStrategy = 'fulltext';
}
