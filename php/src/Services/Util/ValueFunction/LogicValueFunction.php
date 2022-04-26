<?php


namespace Kinintel\Services\Util\ValueFunction;


class LogicValueFunction extends ValueFunctionWithArguments {

    const supportedFunctions = [
        "ifNot",
        "add",
        "subtract"
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
                return is_numeric($value) && is_numeric($addition) ? gmp_strval(gmp_add("$value", "$addition")) : null;


            case "subtract":
                $subtraction = is_numeric($functionArgs[0]) ? $functionArgs[0] : $this->expandMemberExpression($functionArgs[0], $dataItem);
                return is_numeric($value) && is_numeric($subtraction) ? gmp_strval(gmp_sub("$value", "$subtraction")) : null;
        }

        return $value;

    }


    // Expand member expression
    private function expandMemberExpression($expression, $dataItem) {

        $explodedExpression = explode(".", $expression);
        foreach ($explodedExpression as $expression) {
            $dataItem = $dataItem[$expression] ?? null;
        }
        return $dataItem;
    }
}