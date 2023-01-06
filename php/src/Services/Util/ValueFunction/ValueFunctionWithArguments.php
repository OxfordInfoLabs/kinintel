<?php


namespace Kinintel\Services\Util\ValueFunction;

use Kinikit\Core\Logging\Logger;

/**
 * Field value function with arguments in classic function(arg1,arg2) format
 *
 * Class FieldValueFunctionWithArguments
 * @package Kinintel\ValueObjects\Dataset\ValueFunction
 */
abstract class ValueFunctionWithArguments implements ValueFunction {


    /**
     * Implement the does function apply method to split the function name
     * and check our list of applicable functions
     *
     * @param $functionString
     * @return bool|void
     */
    public function doesFunctionApply($functionString) {

        $functionName = explode(" ", $functionString)[0];

        return in_array($functionName, $this->getSupportedFunctionNames());
    }

    /**
     * Apply function
     *
     * @param string $functionString
     * @param mixed $value
     * @return string|void
     */
    public function applyFunction($functionString, $value, $dataItem) {

        $paramsRaw = explode(" ", $functionString, 2);
        $functionName = array_shift($paramsRaw);

        // Match all arguments and return the final match group
        preg_match_all("/[^\s\"']+|\"([^\"]*)\"|'([^']*)'/", $paramsRaw[0] ?? "", $matches);
        $params = $matches[0] ?? [];
        foreach ($matches[2] ?? [] as $index => $match) {
            if ($match) {
                $params[$index] = $match;
            }
        }


        foreach ($params as &$param) {
            $param = $this->processParams($param, $dataItem);
        }

        return $this->applyFunctionWithArgs($functionName, $params ?? [], $value, $dataItem);

    }


    private function processParams($expression, $dataItem) {

        if (is_numeric($expression))
            return $expression;

        $trimmed = trim($expression, "'\"");
        if ($trimmed !== $expression) {
            return $trimmed;
        }

        if ($expression == "null") {
            return null;
        }

        $explodedExpression = explode(".", $expression);
        foreach ($explodedExpression as $expression) {
            if (is_array($dataItem))
                $dataItem = $dataItem[$expression] ?? $expression;
            else
                $dataItem = $expression;
        }

        return $dataItem;

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
    protected abstract function applyFunctionWithArgs($functionName, $functionArgs, $value, $dataItem);


}
