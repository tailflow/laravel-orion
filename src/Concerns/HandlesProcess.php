<?php


namespace Orion\Concerns;
use Illuminate\Database\Eloquent\Model;
use Orion\Facades\OrionBuilder;
use function Functional\select_keys as select;
use ReflectionClass;

/**
 * Trait HandlesProcess
 *
 * @package Orion\Concerns
 */
trait HandlesProcess
{

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    private function postProcess(Model $input, $type)
    {
        $result = null;
        $className = $this->associate($this->model, 0, 2);
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes();

        if ($attributes != null) {

            $value = $attributes[0]->newInstance()->input[$type];
            if ($value) {
                $z = config('orion.composition.job');
                $z[0] = $z[0] . $value[1];

                $a = $this->associate($className, 2, $z);
                if ($a) {
                    $this->params['model'] = $input;
                    $result = OrionBuilder::build('job')->create($this->params, $this->model, $a);
                }
            }
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     */
    private function preprocess($type)
    {
        $result = null;
        $className = $this->associate($this->model,0,2);
        $reflection = new ReflectionClass($className);
        $attributes = $reflection->getAttributes();

        if ($attributes != null){

            $value = $attributes[0]->newInstance()->input[$type] ?? null;
            if ($value){
                $z = config('orion.composition.job');
                $z[0] = $z[0].$value[0];
                $a = $this->associate($className,2, $z);
                if ($a){
                    $result = OrionBuilder::build('job')->create($this->params, $this->model, $a);
                }
            }
        }

        return $result;
    }
}
