<?php

namespace Kinintel\Services\Util\ValueFunction;

class StringValueFunction extends ValueFunctionWithArguments {
    const supportedFunctions = [
        "substring"
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
    protected function applyFunctionWithArgs($functionName, $functionArgs, $value, $dataItem)
    {

        if (is_string($value)) {

            if ($functionName == "substring") {
                $offset = $functionArgs[0];
                $length = $functionArgs[1] ?? null;

                if ($length) {
                    return substr($value, $offset, $length);
                } else {
                    return substr($value, $offset);
                }
            }

            return $value;

        } else {
            return $value;
        }

    }
}