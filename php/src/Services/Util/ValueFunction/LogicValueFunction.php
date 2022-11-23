<?php


namespace Kinintel\Services\Util\ValueFunction;


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
                    return $this->expandMemberExpression($functionArgs[0] ?? "", $dataItem);
                }
                break;
            case "add":
                $addition = is_numeric($functionArgs[0]) ? $functionArgs[0] : $this->expandMemberExpression($functionArgs[0], $dataItem);
                if (is_numeric($value) && is_numeric($addition)){
                    return is_int($value) && is_int($addition) ? gmp_strval(gmp_add("$value", "$addition")) : $value+$addition;
                }else {return null;}

            case "subtract":
                $subtraction = is_numeric($functionArgs[0]) ? $functionArgs[0] : $this->expandMemberExpression($functionArgs[0], $dataItem);
                if (is_numeric($value) && is_numeric($subtraction)){
                    return is_int($value) && is_int($subtraction) ? gmp_strval(gmp_sub("$value", "$subtraction")) : $value-$subtraction;
                }else {return null;}

            case "multiply":
                $multiplier = is_numeric($functionArgs[0]) ? $functionArgs[0] : $this->expandMemberExpression($functionArgs[0], $dataItem);
                if (is_numeric($value) && is_numeric($multiplier)){
                    return is_int($value) && is_int($multiplier) ? gmp_strval(gmp_mul("$value", "$multiplier")) : $value*$multiplier;
                }else {return null;}

            case "divide":
                $divisor = is_numeric($functionArgs[0]) ? $functionArgs[0] : $this->expandMemberExpression($functionArgs[0], $dataItem);
                return is_numeric($value) && is_numeric($divisor) ? $value/$divisor : null;

            case "modulo":
                $modulo = is_numeric($functionArgs[0]) ? $functionArgs[0] : $this->expandMemberExpression($functionArgs[0], $dataItem);
                return is_numeric($value) && is_numeric($modulo) && is_int($value) && is_int($modulo) ?
                    gmp_strval(gmp_div_r("$value", "$modulo")) : (is_numeric($value) && is_numeric($modulo) ? $value % $modulo : null);
            case "floor":
                return is_numeric($value) ? floor($value) : null;

            case "ternary":
                return $value ? $this->expandMemberExpression($functionArgs[0], $dataItem) : $this->expandMemberExpression($functionArgs[1], $dataItem);

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
    private function calculate($operator, $arg1, $arg2){
        switch($operator){
            case "add":

        }
    }

    // Expand member expression
    private function expandMemberExpression($expression, $dataItem) {

        if (is_numeric($expression))
            return $expression;

        $trimmed = trim($expression, "'\"");
        if ($trimmed !== $expression) {
            return $trimmed;
        }

        $explodedExpression = explode(".", $expression);
        foreach ($explodedExpression as $expression) {
            if (is_array($dataItem))
                $dataItem = $dataItem[$expression] ?? null;
            else
                $dataItem = $expression;
        }

        return $dataItem;
    }
}