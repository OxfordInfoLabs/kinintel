<?php


namespace Kinintel\Services\Util\ValueFunction;


use AWS\CRT\Log;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;

class ArrayValueFunction extends ValueFunctionWithArguments
{

    const supportedFunctions = [
        "memberValues",
        "join",
        "slice",
        "item",
        "pop",
        "shift"
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

        if (is_array($value)) {

            if ($functionName == "memberValues") {
                $member = $functionArgs[0] ?? "";
                $values = [];
                foreach ($value as $item) {
                    $values[] = $item[$member] ?? null;
                }
                return $values;
            }

            if ($functionName == "join") {
                $separator = $functionArgs[0] ?? ",";
                return implode($separator, $value);
            }

            if ($functionName == "slice") {
                $offset = $functionArgs[0];
                $length = $functionArgs[1] ?? null;

                return array_slice($value, $offset, $length);
            }

            if ($functionName == "item") {
                return $value[$functionArgs[0]] ?? null;
            }

            if ($functionName == "pop") {
                return array_pop($value);
            }

            if ($functionName == "shift") {
                return array_shift($value);
            }

            return $value;

        } else {
            return $value;
        }

    }
}