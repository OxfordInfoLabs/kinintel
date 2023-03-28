<?php


namespace Kinintel\Services\Util;


use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;

class ParameterisedStringEvaluator {

    /**
     * @var ValueFunctionEvaluator
     */
    private $valueFunctionEvaluator;

    /**
     * ParameterisedStringEvaluator constructor.
     *
     * @param ValueFunctionEvaluator $valueFunctionEvaluator
     */
    public function __construct($valueFunctionEvaluator) {
        $this->valueFunctionEvaluator = $valueFunctionEvaluator;
    }


    /**
     * Evaluate a parameterised string.  An array of field values can be passed.  These will expand
     * placeholders supplied in [[XXX]] format.  An array of parameter values can also be passed.  These
     * will expand placeholders supplied in {{XXX}} format.
     *
     * @param $string
     * @param array $fieldValues
     * @param array $parameterValues
     */
    public function evaluateString($string, $fieldValues = [], $parameterValues = []) {

        // Evaluate any field values and parameters
        $string = $this->valueFunctionEvaluator->evaluateString($string, $fieldValues, ["[[", "]]"]);
        return $this->valueFunctionEvaluator->evaluateString($string, $parameterValues, ["{{", "}}"]);

    }

}