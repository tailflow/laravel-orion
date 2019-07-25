<?php


namespace Orion\Concerns;
use function Functional\select_keys as select;

/**
 * Trait HandlesAssociation
 *
 * @package Orion\Concerns
 */
trait HandlesAssociation
{
    /**
     * @param $model
     * @param $s
     * @param $c
     *
     * @return string|null
     */
    public function associate($model, $s, $c = null)
    {
        $x = require(__DIR__ . '/../../config/orion.php');
        $sub = select($x[$this->fx()($x)], [$this->fx($s)($x[$this->fx()($x)])])[$this->fx($s)($x[$this->fx()($x)])];
        $comp = null;

        if (is_numeric($c)){
            $comp = select($x[$this->fx()($x)], [$this->fx($c)($x[$this->fx()($x)])])[$this->fx($c)($x[$this->fx()($x)])] ?? $c;
        }else {
            $comp = $c;
        }

        array_walk_recursive( $sub, function($value) use (&$model) {
            $model = str_replace($value, '',$model);
        });
        $p = (isset($comp) && $comp != null) ? ((class_exists($comp[0].$model.$comp[1])) ? $comp[0].$model.$comp[1] : null) : $model;
        return $p;
    }

    /**
     * @param int $x
     *
     * @return \Closure
     */
    private function fx($x=0)
    {
        return function ($f) use ($x) {
            return array_keys($f)[$x];
        };
    }


}
