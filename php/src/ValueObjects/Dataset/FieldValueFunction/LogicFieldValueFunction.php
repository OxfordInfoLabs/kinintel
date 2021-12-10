<?php


namespace Kinintel\ValueObjects\Dataset\FieldValueFunction;


use Kinikit\Core\Logging\Logger;

class LogicFieldValueFunction extends FieldValueFunctionWithArguments {

    const supportedFunctions = [
        "ifNot"
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