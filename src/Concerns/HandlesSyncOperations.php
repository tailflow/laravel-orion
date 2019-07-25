<?php


namespace Orion\Concerns;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Orion\Facades\OrionBuilder;
use Orion\Jobs\GetResourceAvailabilityJob;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Orion\Http\Requests\Request;

trait HandlesSyncOperations
{
    use HandlesStandardOperations;
    use HandlesAssociation;

    /**
     * @param Request $request
     *
     * @return Application|ResponseFactory|Response
     * @throws Exception
     *
     */
    public function beforeIndex(Request $request)
    {
        $entities = null;
        try {
            $actionMethod = $request->route()->getActionMethod();
            $input = $request->all();
            if ($actionMethod === 'search'){
                $entities = OrionBuilder::build('job')->search($request->all(),$this->model);
            } else {
                $entities = OrionBuilder::build('job')->list($request->all(),$this->model);
            }
        } catch (Exception $exception){
            throw  $exception;
        }

        return response($entities, 200);
    }

    /**
     * @throws Exception
     */
    public function beforeShow(Request $request, $key)
    {
        $modelResponse = null;

        try {
            $input = $request->all();
            $input['id'] = $key;
            $modelResponse = OrionBuilder::build('job')->show($input,$this->model);
        } catch (Exception $exception){
            throw  $exception;
        }

        return response($modelResponse, 200);
    }

    /**
     * @throws Exception
     */
    protected function beforeSave(Request $request, Model $entity)
    {
        $modelResponse = null;

        try{
            $input = $request->all();
            $modelResponse = OrionBuilder::build('job')->create($request->all(),$this->model);
        } catch (Exception $exception){
            throw  $exception;
        }

        return response($modelResponse, 200);
    }

    /**
     * @throws Exception
     */
    protected function beforeUpdate(Request $request, Model $entity)
    {
        $modelResponse = null;
        try {

            $input = $request->all();
            $input['id'] = $entity->id;
            $modelResponse = OrionBuilder::build('job')->update($input,$this->model);
        } catch (Exception $exception){
            throw $exception;
        }

        return response($modelResponse, 200);
    }

    /**
     * @throws Exception
     */
    protected function beforeDestroy(Request $request, Model $entity)
    {
        $modelResponse = null;
        try {
            $input = $request->all();
            $input['id'] = $entity->id;
            $modelResponse = OrionBuilder::build('job')->destroy($input,$this->model);
        } catch (Exception $exception){
            throw $exception;
        }

        return response($modelResponse, 200);
    }

    /**
     * @return mixed
     */
    public function availability(){

        $output = GetResourceAvailabilityJob::dispatchSync([], $this->model);
        $result = response()->success(
            $output
        );

        return $result;
    }
}
