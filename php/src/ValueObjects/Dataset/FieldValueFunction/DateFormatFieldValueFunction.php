<?php


namespace Kinintel\ValueObjects\Dataset\FieldValueFunction;


use Kinikit\Core\Logging\Logger;

class DateFormatFieldValueFunction extends FieldValueFunctionWithArguments {

    const supportedFunctions = [
        "ensureDateFormat",
        "dateConvert",
        "dayOfMonth",
        "dayOfWeek",
        "dayName",
        "monthName",
        "month",
        "year"
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
     * Apply function with args
     *
     * @param $functionName
     * @param $functionArgs
     * @param $value
     * @return mixed|void
     */
    protected function applyFunctionWithArgs($functionName, $functionArgs, $value, $dataItem) {

        $standardDate = date_create_from_format("Y-m-d", substr($value, 0, 10));

        switch ($functionName) {
            case "ensureDateFormat":
                $date = date_create_from_format($functionArgs[0] ?? "", $value);
                return $date ? $value : null;
            case "dateConvert":
                $date = date_create_from_format($functionArgs[0] ?? "", $value);
                return $date && ($functionArgs[1] ?? null) ? $date->format($functionArgs[1]) : null;
            case "dayOfMonth":
                return $standardDate ? $standardDate->format("d") : null;
            case "dayOfWeek":
                return $standardDate ? $standardDate->format("w") + 1 : null;
            case "dayName":
                return $standardDate ? $standardDate->format("l") : null;
            case "month":
                return $standardDate ? $standardDate->format("m") : null;
            case "monthName":
                if (is_numeric($value)) {
                    $date = date_create_from_format("d/m/Y", "01/$value/2000");
                    return $date ? $date->format("F") : null;
                } else
                    return $standardDate ? $standardDate->format("F") : null;
            case "year":
                return $standardDate ? $standardDate->format("Y") : null;

        }
    }
}