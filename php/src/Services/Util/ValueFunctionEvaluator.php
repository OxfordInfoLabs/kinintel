<?php


namespace Kinintel\Services\Util;


use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Services\Util\ValueFunction\ConversionValueFunction;
use Kinintel\Services\Util\ValueFunction\DateFormatValueFunction;
use Kinintel\Services\Util\ValueFunction\ValueFunction;
use Kinintel\Services\Util\ValueFunction\LogicValueFunction;
use Kinintel\Services\Util\ValueFunction\RegExValueFunction;

class ValueFunctionEvaluator {

    /**
     * @var ValueFunction[]
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
            new RegExValueFunction(),
            new DateFormatValueFunction(),
            new LogicValueFunction(),
            new ConversionValueFunction()
        ];
    }


    /**
     * Add a new function for field value evaluation
     *
     * @param $function
     */
    public function addValueFunction($function) {
        $this->functions[] = $function;
    }

    /**
     * Evaluate a string for field value functions where parameterised values
     * are expected to be supplied surrounded by delimiters and fulfilled using the
     * data array
     *
     * @param $string
     * @param string[] $delimiters
     * @param array $data
     */
    public function evaluateString($string, $data = [], $delimiters = ["[[", "]]"]) {

        $evaluated = preg_replace_callback("/" . preg_quote($delimiters[0]) . "(.*?)" . preg_quote($delimiters[1]) . "/", function ($matches) use ($data) {

            $exploded = explode(" | ", $matches[1]);

            $expression = trim($exploded[0]);

            // Handle special built in expressions
            $specialExpression = $this->evaluateSpecialExpressions($expression);

            if ($specialExpression == $expression) {

                // assume field expression
                $value = $this->expandMemberExpression($expression, $data);
            } else {

                // Set as special expression
                $value = $specialExpression;
            }

            if (sizeof($exploded) > 1) {
                for ($i = 1; $i < sizeof($exploded); $i++) {
                    $value = $this->evaluateValueFunction(trim($exploded[$i]), $value, $data);
                }
            }

            return $value;

        }, $string);
        return $evaluated !== "" ? $evaluated : null;

    }


    /**
     * Evaluate value function based upon first matching function
     *
     * @param $functionString
     * @param $fieldValue
     */
    public function evaluateValueFunction($functionString, $fieldValue, $itemData) {
        foreach ($this->functions as $function) {
            if ($function->doesFunctionApply($functionString)) {
                return $function->applyFunction($functionString, $fieldValue, $itemData);
            }
        }
        return $fieldValue;
    }


    // Expand member expression
    private function expandMemberExpression($expression, $dataItem) {

        $explodedExpression = explode(".", $expression);
        foreach ($explodedExpression as $expression) {
            $dataItem = $dataItem[$expression] ?? null;
        }
        return $dataItem;
    }


    private function evaluateSpecialExpressions($expression) {

        if ($expression == "NOW") {
            $expression = date("Y-m-d H:i:s");
        }

        // Evaluate time offset parameters for days ago and hours ago
        $expression = preg_replace_callback("/([0-9]+)_DAYS_AGO/", function ($matches) use (&$outputParameters) {
            return (new \DateTime())->sub(new \DateInterval("P" . $matches[1] . "D"))->format("Y-m-d H:i:s");
        }, $expression);

        $expression = preg_replace_callback("/([0-9]+)_HOURS_AGO/", function ($matches) use (&$outputParameters) {
            return (new \DateTime())->sub(new \DateInterval("PT" . $matches[1] . "H"))->format("Y-m-d H:i:s");
        }, $expression);

        return $expression;
    }


}