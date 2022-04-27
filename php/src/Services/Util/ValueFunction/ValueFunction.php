<?php


namespace Kinintel\Services\Util\ValueFunction;


interface ValueFunction {

    /**
     * Does a value function apply to the passed function string
     *
     * @param $functionString
     * @return boolean
     */
    public function doesFunctionApply($functionString);


    /**
     * Apply this function to the built expression and return an evaluated value
     *
     * @param string $functionString
     * @param string $value
     * @return string
     */
    public function applyFunction($functionString, $value, $dataItem);

}