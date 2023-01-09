<?php

namespace Kinintel\Services\Util\ValueFunction;

use Kinikit\Core\Logging\Logger;

class StringValueFunction extends ValueFunctionWithArguments {
    const supportedFunctions = [
        "substring",
        "concat",
        "toUTF8",
        "trim"
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
                        $string .= $arg;
                    }

                    return $string;

                case "toUTF8":
                    return preg_replace('/(\xF0\x9F[\x00-\xFF][\x00-\xFF])/', "", $value) == $value ? $value : null;

                case "trim":
                    return trim($value, $functionArgs[0]);
            }

            return $value;

        } else {
            return $value;
        }

    }

}
