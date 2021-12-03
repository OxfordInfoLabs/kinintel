<?php


namespace Kinintel\Services\Dataset;


use Kinintel\ValueObjects\Dataset\FieldValueFunction\DateFormatFieldValueFunction;
use Kinintel\ValueObjects\Dataset\FieldValueFunction\FieldValueFunction;
use Kinintel\ValueObjects\Dataset\FieldValueFunction\RegExFieldValueFunction;

class FieldValueFunctionEvaluator {

    /**
     * @var FieldValueFunction[]
     */
    private $functions;


    /**
     * Construct, install standard functions
     *
     * FieldValueFunctionEvaluator constructor.
     */
    public function __construct() {

        // Add built in evaluators
        $this->functions = [
            new RegExFieldValueFunction(),
            new DateFormatFieldValueFunction()
        ];
    }


    /**
     * Add a new function for field value evaluation
     *
     * @param $function
     */
    public function addFieldValueFunction($function) {
        $this->functions[] = $function;
    }


    /**
     * Evaluate field value function based upon first matching function
     *
     * @param $functionString
     * @param $fieldValue
     */
    public function evaluateFieldValueFunction($functionString, $fieldValue) {
        foreach ($this->functions as $function) {
            if ($function->doesFunctionApply($functionString)) {
                return $function->applyFunction($functionString, $fieldValue);
            }
        }
        return $fieldValue;
    }


}