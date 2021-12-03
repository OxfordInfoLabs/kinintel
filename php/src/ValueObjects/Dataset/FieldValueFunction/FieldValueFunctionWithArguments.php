<?php


namespace Kinintel\ValueObjects\Dataset\FieldValueFunction;

/**
 * Field value function with arguments in classic function(arg1,arg2) format
 *
 * Class FieldValueFunctionWithArguments
 * @package Kinintel\ValueObjects\Dataset\FieldValueFunction
 */
abstract class FieldValueFunctionWithArguments implements FieldValueFunction {


    /**
     * Implement the does function apply method to split the function name
     * and check our list of applicable functions
     *
     * @param $functionString
     * @return bool|void
     */
    public function doesFunctionApply($functionString) {

        $functionName = explode("(", $functionString)[0];

        return in_array($functionName, $this->getSupportedFunctionNames());
    }

    /**
     * Apply function
     *
     * @param string $functionString
     * @param string $value
     * @return string|void
     */
    public function applyFunction($functionString, $value) {

        $functionName = explode("(", $functionString)[0];
        preg_match_all("/'(.*?)'/", $functionString, $matches);

        return $this->applyFunctionWithArgs($functionName, $matches[1] ?? [], $value);

    }


    /**
     * Return list of supported function names this function supports
     *
     * @return string[]
     */
    protected abstract function getSupportedFunctionNames();

    /**
     * Apply function with args
     *
     * @param $functionName
     * @param $functionArgs
     * @param $value
     * @return mixed
     */
    protected abstract function applyFunctionWithArgs($functionName, $functionArgs, $value);


}