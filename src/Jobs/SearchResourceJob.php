<?php


namespace Orion\Jobs;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Concerns\HandlesProcess;
use Orion\Facades\OrionBuilder;

class SearchResourceJob extends Job implements ShouldQueue
{

    use Dispatchable, HandlesProcess;

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

            $this->params['exposedScopes'] = [];

            $chain = $this->preprocess('search');

            if ($chain) {
                $this->params = array_replace_recursive($this->params, $chain);
            }
            $isNone = ($this->params['chain'] ?? null) === 'none' ? true : false;

            if ($isNone) {
                $result = $chain;
            } else {
                $result = OrionBuilder::build('query')->setModel($this->model)->search($this->params);
                $chain = $this->postProcess($result, 'search');
                if ($chain) {
                    $result = array_replace_recursive($result->toArray(), $chain);
                }
            }

        } catch (Exception $exception) {
            throw $exception;
        }

        return $result;
    }

}
