<?php


namespace Orion\Jobs;


use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Orion\Concerns\HandlesProcess;
use Orion\Jobs\Job;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Facades\OrionBuilder;

class GetAllResourceJob extends Job implements ShouldQueue
{

    use Dispatchable, HandlesProcess, AuthorizesRequests;

    /**
     * GetAllImageLogsJob constructor.
     *
     * @param array  $params
     * @param string $model
     */
    public function __construct(array $params,
                                protected string $model)
    {
        parent::__construct($params);
    }

    /**
     * gets all brands
     *
     * @return LengthAwarePaginator|Collection|array
     * @throws Exception
     */
    public function handle(): LengthAwarePaginator|Collection|array
    {
        $result = [];
        try {

            $chain = $this->preprocess('get');

            if ($chain) {
                $this->params = array_replace_recursive($this->params, $chain);
            }
            $isNone = ($this->params['chain'] ?? null) === 'none' ? true : false;

            if ($isNone) {
                $result = $chain;
            } else {
                /** @var LengthAwarePaginator $collection */
                $result = OrionBuilder::build('query')->setModel($this->model)->list($this->params);
                $chain = $this->postProcess($result, 'get');
                if ($chain)
                {
                    $result = array_replace_recursive($result->toArray(), $chain);
                }
            }

        } catch (Exception $exception) {
            throw $exception;
        }

        return $result;
    }

}
