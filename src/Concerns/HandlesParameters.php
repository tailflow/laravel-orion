<?php


namespace Orion\Concerns;


use Safe\Exceptions\ArrayException;

trait HandlesParameters
{

    /**
     * Get values from the params array
     *
     * @param string|null $param
     *
     * @return array|mixed|null
     * @throws ArrayException
     */
    protected function getParam(string $param = null)
    {
        if (isset($param)){
            return $param;
        }

        if (!$this->array_key_exists($param, $this->params)) {
            throw new \Exception('PARAMETER NOT FOUND');
        }

        $val = $this->params[$param];

        return $val;
    }

    function scrub(string $param){
        if ($this->params != null && $this->array_key_exists($param, $this->params)){
            unset($this->params[$param]);
        }
    }

    /**
     * @throws ArrayException
     */
    private function array_key_exists(string $param, array $parameters): bool
    {
        error_clear_last();
        $result = \array_key_exists($param,$parameters);
        if ($result === false) {
            throw ArrayException::createFromPhpError();
        }
        return $result;
    }
}
