<?php


namespace Orion\Jobs;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Concerns\HandlesProcess;
use Orion\Facades\OrionBuilder;

class GetResourceWithIdJob extends Job implements ShouldQueue
{
    use Dispatchable, HandlesProcess;

    /**
     * Create a new job instance.
     *
     * @param array  $params
     * @param string $model
     */
    public function __construct(array $params, protected string $model)
    {
        parent::__construct($params);
    }

    /**
     *
     * @return Model|array
     */
    public function handle():Model|array
    {
        $result = [];
        $chain = $this->preprocess('getById');

        if ($chain) {
            $this->params = array_replace_recursive($this->params, $chain);
        }
        $isNone = ($this->params['chain'] ?? null) === 'none' ? true : false;

        if ($isNone) {
            $result = $chain;
        } else {
            $result = OrionBuilder::build('query')->setModel($this->model)->getById($this->params);
            $chain = $this->postProcess($result, 'getById');
            if ($chain) {
                $result = $result->fresh();
            }
        }

        return $result;
    }
}
