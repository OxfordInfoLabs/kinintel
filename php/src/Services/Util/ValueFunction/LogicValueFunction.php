<?php


namespace Kinintel\Services\Util\ValueFunction;


use AWS\CRT\Log;
use Kinikit\Core\Logging\Logger;

class LogicValueFunction extends ValueFunctionWithArguments {

    const supportedFunctions = [
        "ifNot",
        "add",
        "subtract",
        "multiply",
        "divide",
        "modulo",
        "floor",
        "ternary",
        "equals",
        "notequals",
        "gt",
        "gte",
        "lt",
        "lte"
    ];


    /**
     * Get supported function names
     *
     * @return string[]|void
     */
    protected function getSupportedFunctionNames() {
        return self::supportedFunctions;
    }

    /**
     * Apply a function with arguments
     *
     * @param $functionName
     * @param $functionArgs
     * @param $value
     * @param $dataItem
     *
     * @return mixed|void
     */
    protected function applyFunctionWithArgs($functionName, $functionArgs, $value, $dataItem) {

        switch ($functionName) {
            case "ifNot":
                if (!$value) {
                    return $functionArgs[0] ?? "";
                }
                break;
            case "add":
                $addition = $functionArgs[0];
                if (is_numeric($value) && is_numeric($addition)) {
                    return is_int($value) && is_int($addition) ? gmp_strval(gmp_add("$value", "$addition")) : $value + $addition;
                } else {
                    return null;
                }

            case "subtract":
                $subtraction = $functionArgs[0];
                if (is_numeric($value) && is_numeric($subtraction)) {
                    return is_int($value) && is_int($subtraction) ? gmp_strval(gmp_sub("$value", "$subtraction")) : $value - $subtraction;
                } else {
                    return null;
                }

            case "multiply":
                $multiplier = $functionArgs[0];
                if (is_numeric($value) && is_numeric($multiplier)) {
                    return is_int($value) && is_int($multiplier) ? gmp_strval(gmp_mul("$value", "$multiplier")) : $value * $multiplier;
                } else {
                    return null;
                }

            case "divide":
                $divisor = $functionArgs[0];
                return is_numeric($value) && is_numeric($divisor) ? $value / $divisor : null;

            case "modulo":
                $modulo = $functionArgs[0];
                return is_numeric($value) && is_numeric($modulo) && is_int($value) && is_int($modulo) ?
                    gmp_strval(gmp_div_r("$value", "$modulo")) : (is_numeric($value) && is_numeric($modulo) ? $value % $modulo : null);
            case "floor":
                return is_numeric($value) ? floor($value) : null;

            case "ternary":
                return $value ? $functionArgs[0] : $functionArgs[1];

            case "equals":
                return ($value == $functionArgs[0]);

            case "notequals":
                return ($value != $functionArgs[0]);

            case "gt":
                return ($value > $functionArgs[0]);

            case "gte":
                return ($value >= $functionArgs[0]);

            case "lt":
                return ($value < $functionArgs[0]);

            case "lte":
                return ($value <= $functionArgs[0]);
        }

        return $value;

    }

    // Maths gmp functions
    private function calculate($operator, $arg1, $arg2) {
        switch ($operator) {
            case "add":

        }
    }

}
