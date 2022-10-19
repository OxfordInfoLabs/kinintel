<?php

namespace Kinintel\Services\Util\ValueFunction;

class ObjectValueFunction extends ValueFunctionWithArguments {
    const supportedFunctions = [
        "member"
    ];

    /**
     * Get the supported functions returned for this value function
     *
     * @return string[]|void
     */
    protected function getSupportedFunctionNames()
    {
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

        if (is_array($value)) {

            if ($functionName == "member") {
                return $value[$functionArgs[0]];
            }

            return $value;

        } else {
            return $value;
        }

    }
}