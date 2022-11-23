<?php

namespace Kinintel\Services\Util\ValueFunction;

use Kinikit\Core\Logging\Logger;

class StringValueFunction extends ValueFunctionWithArguments {
    const supportedFunctions = [
        "substring",
        "concat"
    ];

    /**
     * Get the supported functions returned for this value function
     *
     * @return string[]|void
     */
    protected function getSupportedFunctionNames() {
        return self::supportedFunctions;
    }


    /**
     * Apply one of the supported functions and return
     *
     * @param $functionName
     * @param $functionArgs
     * @param $value
     * @param $dataItem
     * @return mixed|void
     */
    protected function applyFunctionWithArgs($functionName, $functionArgs, $value, $dataItem) {

        if (is_string($value)) {

            switch ($functionName) {
                case "substring":
                    $offset = $functionArgs[0];
                    $length = $functionArgs[1] ?? null;

                    if ($length) {
                        return substr($value, $offset, $length);
                    } else {
                        return substr($value, $offset);
                    }

                case "concat":
                    $string = $value;
                    foreach ($functionArgs as $arg) {
                        $string .= $this->expandMemberExpression($arg, $dataItem);
                    }

                    return $string;
            }

            return $value;

        } else {
            return $value;
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
            if (is_array($dataItem)) {
                $dataItem = $dataItem[$expression] ?? $expression;
            } else {
                $dataItem = $expression;
            }
        }

        return $dataItem;
    }
}