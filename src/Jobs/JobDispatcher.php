<?php


namespace Orion\Jobs;

use Orion\Concerns\EloquentBuilder;
use Orion\Concerns\HandlesAssociation;

class JobDispatcher
{
    use EloquentBuilder, HandlesAssociation;

    /**
     * The underlying faker instance.
     *
     * @var
     */
    private  $instance;

    public function __construct(){
        $this->instance = $this;
    }

    public function create($input, $model, $job = null)
    {
        return ($job == null)?CreateResourceJob::dispatchSync($input, $model) : dispatch_now(new $job($input));
    }

    public function list($input, $model,$job = null)
    {
        return ($job == null)?GetAllResourceJob::dispatchSync($input, $model) : dispatch_now(new $job($input));
    }

    public function search($input, $model)
    {
        return SearchResourceJob::dispatchSync($input, $model);
    }

    public function show($input, $model)
    {
        return GetResourceWithIdJob::dispatchSync($input, $model);
    }

    public function update($input, $model, $job = null)
    {
        return ($job == null)?UpdateResourceJob::dispatchSync($input, $model) : dispatch_now(new $job($input));
    }

    public function destroy($input, $model)
    {
        return DeleteResourceJob::dispatchSync($input, $model);
    }

    public function seed($input, $model)
    {
        return SeedResourceJob::dispatchSync($input, $model);
    }

    public function invoke($callback)
    {
        $reflectionFunction  = new  \ReflectionFunction ($callback);
        $parameters = $reflectionFunction->getParameters();
        $attributes = $parameters[0]->getAttributes();
        $any = $attributes[0]->getArguments();
        $path = config('orion.namespaces.jobs');
        $className = $path  . $any[0][0] . 'Job';

        $params = $callback([]);
        // resolve job to name
        return $className::dispatchSync($params);
    }
}
