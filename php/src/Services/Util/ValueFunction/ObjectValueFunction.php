<?php

namespace Kinintel\Services\Util\ValueFunction;

class ObjectValueFunction extends ValueFunctionWithArguments {
    const supportedFunctions = [
        "member",
        "keyValueArray"
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

            if ($functionName == "keyValueArray") {
                $returnArray = [];
                $propertyKey = $functionArgs[0] ?? 'key';
                $valueKey = $functionArgs[1] ?? 'value';

                foreach($value as $key => $item) {
                    $object = [];
                    $object[$propertyKey] = $key;
                    $object[$valueKey] = $item;

                    $returnArray[] = $object;
                }

                return $returnArray;
            }

            return $value;

        } else {
            return $value;
        }

    }
}
