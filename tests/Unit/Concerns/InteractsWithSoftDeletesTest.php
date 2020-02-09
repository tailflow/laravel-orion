<?php

namespace Orion\Tests\Unit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Concerns\InteractsWithSoftDeletes;
use Orion\Tests\Unit\TestCase;

class InteractsWithSoftDeletesTest extends TestCase
{
    /** @test */
    public function soft_deletes_when_soft_deletes_trait_is_applied_on_model()
    {
        $stub = new InteractsWithSoftDeletesStub();

        $this->assertTrue($stub->softDeletes(ModelStubWithSoftDeletes::class));
    }

    /** @test */
    public function soft_deletes_when_soft_deletes_trait_is_not_applied_on_model()
    {
        $stub = new InteractsWithSoftDeletesStub();

        $this->assertFalse($stub->softDeletes(ModelStubWithoutSoftDeletes::class));
    }
}

class ModelStubWithSoftDeletes extends Model
{
    use SoftDeletes;
}

class ModelStubWithoutSoftDeletes extends Model
{

}

class InteractsWithSoftDeletesStub
{
    use InteractsWithSoftDeletes;
}
