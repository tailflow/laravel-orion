<?php

namespace Orion\Tests\Unit\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Orion\Http\Controllers\BaseController;
use Orion\Tests\Fixtures\App\Http\Requests\TagRequest;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Tag;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Unit\TestCase;

class BaseControllerTest extends TestCase
{
    /** @test */
    public function authorize()
    {
        $user = new User(['name' => 'test user']);
        $ability = 'create';
        $arguments = [Tag::class];

        $controllerMock = Mockery::mock(BaseControllerStub::class)->makePartial();
        $controllerMock->shouldReceive('resolveUser')->once()->withNoArgs()->andReturn($user);
        $controllerMock->shouldReceive('authorizeForUser')->once()->with($user, $ability, $arguments)->andReturn(true);

        $this->assertTrue($controllerMock->authorize($ability, $arguments));
    }

    /** @test */
    public function creating_new_model_query()
    {
        $stub = new BaseControllerStub();

        $newModelQuery = $stub->newModelQuery();

        $this->assertInstanceOf(Builder::class, $newModelQuery);
        $this->assertInstanceOf(Tag::class, $newModelQuery->getModel());
    }

    /** @test */
    public function resolving_model_class()
    {
        $stub = new BaseControllerStub();

        $this->assertEquals(Tag::class, $stub->resolveModelClass());
    }
}

class BaseControllerStub extends BaseController
{
    protected static $model = Tag::class;

    protected static $request = TagRequest::class;

    /**
     * @inheritDoc
     */
    public function resolveResourceModelClass(): string
    {
        return Post::class;
    }
}
