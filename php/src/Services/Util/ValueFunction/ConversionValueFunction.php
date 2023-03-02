<?php


namespace Kinintel\Services\Util\ValueFunction;


class ConversionValueFunction extends ValueFunctionWithArguments {

    const supportedFunctions = [
        "toJSON",
        "toNumber"
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
     * Apply a function with args
     *
     * @param $functionName
     * @param $functionArgs
     * @param $value
     * @param $dataItem
     * @return mixed|void
     */
    protected function applyFunctionWithArgs($functionName, $functionArgs, $value, $dataItem) {
        switch ($functionName) {
            case "toJSON":
                return $value ? json_encode($value) : $value;
            case "toNumber":
                $value = preg_replace("/[^0-9]/", "", $value ?? "");
                if (is_numeric($value)){
                    return strpos($value, ".") ? floatval($value) : intval($value);
                } else {
                    return $functionArgs[0] ?? null;
                }
        }
    }
}