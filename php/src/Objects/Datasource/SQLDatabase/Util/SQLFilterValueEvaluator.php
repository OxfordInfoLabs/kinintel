<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\TemplateParser;

class SQLFilterValueEvaluator {


    /**
     * Evaluate a filter value using all required rules
     *
     * @param $value
     * @param array $templateParameters
     */
    public function evaluateFilterValue($value, $templateParameters = [], $tableAlias = null, &$outputParameters = []) {

        $valueArray = is_array($value) ? $value : [$value];

        $valueStrings = [];
        foreach ($valueArray as $valueEntry) {

            // Remove any [[ from column names and prefix with table alias if supplied
            $value = preg_replace("/\[\[(.*?)\]\]/", ($tableAlias ? $tableAlias . "." : "") . "$1", $valueEntry);

            // Replace any template parameters
            $value = preg_replace_callback("/([\*%]*){{(.*?)}}([\*%]*)/", function ($matches) use (&$outputParameters, $templateParameters) {
                $matchingParamValue = $templateParameters[$matches[2]] ?? null;
                $valueArray = is_array($matchingParamValue) ? $matchingParamValue : [$matchingParamValue];
                foreach ($valueArray as $matchingParamValueElement) {
                    $outputParameters[] = ($matches[1] ? "%" : "") . $matchingParamValueElement . ($matches[3] ? "%" : "");
                }
                return str_repeat("?,", sizeof($valueArray) - 1) . "?";
            }, $value);

            // Evaluate time offset parameters for days ago and hours ago
            $value = preg_replace_callback("/([0-9]+)_DAYS_AGO/", function ($matches) use (&$outputParameters) {
                $outputParameters[] = (new \DateTime())->sub(new \DateInterval("P" . $matches[1] . "D"))->format("Y-m-d H:i:s");
                return "?";
            }, $value);

            $value = preg_replace_callback("/([0-9]+)_HOURS_AGO/", function ($matches) use (&$outputParameters) {
                $outputParameters[] = (new \DateTime())->sub(new \DateInterval("PT" . $matches[1] . "H"))->format("Y-m-d H:i:s");
                return "?";
            }, $value);

            // If no substutions assume value is single value
            if ($value == $valueEntry) {
                $outputParameters[] = $value;
                $value = "?";
            }

            $valueStrings[] = $value;

        }

        return join(",", $valueStrings);

    }


}