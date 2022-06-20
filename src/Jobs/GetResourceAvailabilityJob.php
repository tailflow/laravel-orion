<?php


namespace Orion\Jobs;


use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Orion\Concerns\HandlesProcess;
use Orion\Facades\OrionBuilder;

class GetResourceAvailabilityJob extends Job implements ShouldQueue
{

    use Dispatchable, HandlesProcess;

    /**
     * Delete job instance.
     *
     * @param array  $params
     * @param string $model
     */
    public function __construct(array $params, protected string $model)
    {
        parent::__construct($params);
    }

    /**
     * Execute the job.
     *
     * @return array
     * @throws Exception
     */
    public function handle(): array
    {
        try {
            $chain = $this->preprocess('availability');

            if ($chain) {
                $this->params = array_replace_recursive($this->params, $chain);
            }
            $isNone = ($this->params['chain'] ?? null) === 'none' ? true : false;

            if ($isNone) {
                $result = $chain;
            }else {
                $output = OrionBuilder::build('query')->setModel($this->model)->getAvailability();
                $result = ['output' => $output];
                $chain = $this->postProcess($result, 'availability');
                if ($chain) {
                    $result = $result->fresh();
                }
            }

        } catch (Exception $e) {
            throw $e;
        }

        return $result;
    }
}
