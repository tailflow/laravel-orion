<?php


namespace Orion\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Facades\OrionBuilder;
use ReflectionClass;
use Orion\Concerns\HandlesProcess;

class UpdateResourceJob extends Job implements ShouldQueue
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
    public function handle(): Model|array
    {
        $result = [];

        $chain = $this->preprocess('update');

        if ($chain) {
            $this->params = array_merge_recursive($chain, $this->params);
            $result = $chain;
        }

        $isNone = ($this->params['chain'] ?? null) === 'none' ? true : false;

        if ($isNone) {
            $result = $chain;
        } else {
            $responseModel = OrionBuilder::build('query')->setModel($this->model)->update($this->params);
            $result = $responseModel;

            $chain = $this->postProcess($result, 'update');
            if ($chain) {
                $result = array_replace_recursive($result->toArray(), $chain);
            }
        }
        return $result;
    }
}
