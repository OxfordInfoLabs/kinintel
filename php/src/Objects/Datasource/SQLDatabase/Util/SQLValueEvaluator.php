<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;


use DateInterval;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Services\Util\SQLClauseSanitiser;

class SQLValueEvaluator {

    /**
     * @var SQLClauseSanitiser
     */
    private $sqlClauseSanitiser;


    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;


    /**
     * SQLFilterValueEvaluator constructor.
     *
     * @param DatabaseConnection $databaseConnection
     */
    public function __construct($databaseConnection) {
        $this->sqlClauseSanitiser = Container::instance()->get(SQLClauseSanitiser::class);
        $this->databaseConnection = $databaseConnection;
    }


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

            // Replace any template parameters
            $value = preg_replace_callback("/([\*%]*){{(.*?)}}([\*%]*)/", function ($matches) use (&$outputParameters, $templateParameters) {
                $matchingParamValue = $templateParameters[$matches[2]] ?? null;
                $valueArray = is_array($matchingParamValue) ? $matchingParamValue : [$matchingParamValue];
                $literals = [];
                foreach ($valueArray as $matchingParamValueElement) {
                    // For parameters, we will check if they're wrapped in quotes and substitute them as a literal at the end if they are (hence #~#)
                    if ($matches[1] || !is_numeric($matchingParamValueElement))
                        $literals[] = "#~p~#" . ($matches[1] ? "%" : "") . $matchingParamValueElement . ($matches[3] ? "%" : "") . "#~p~#";
                    else if (is_numeric($matchingParamValueElement))
                        $literals[] = $matchingParamValueElement;
                }
                return join(",", $literals);
            }, $valueEntry ?? "");



            $toIntervalStr = [
                "_YEARS_AGO" => (fn($n) => "P" . $n . "Y"),
                "_MONTHS_AGO" => (fn($n) => "P" . $n . "M"),
                "_DAYS_AGO" => (fn($n) => "P" . $n . "D"),
                "_HOURS_AGO" => (fn($n) => "PT" . $n . "H"),
                "_MINUTES_AGO" => (fn($n) => "PT" . $n . "M"),
                "_SECONDS_AGO" => (fn($n) => "PT" . $n . "S"),
            ];

            // Evaluate time offset parameters for days ago and hours ago
            foreach ($toIntervalStr as $suffix => $toInterval){
                $value = preg_replace_callback("/'*([0-9]+)$suffix'*/", function ($matches) use ($toInterval) {
                    return "#~d~#" . (new \DateTime())->sub(new DateInterval($toInterval($matches[1])))->format("Y-m-d H:i:s") . "#~d~#";
                }, $value);
            }

            //Substitute the #~# back in for {{param}} if the variable is exposed
            $value = preg_replace_callback("/'[^']*?'/", function($matches){ //Foreach group of quotes
                return str_replace("#~p~#", "", $matches[0]); //Remove the #~#
            }, $value);
            $value = str_replace("#~p~#", "'", $value); //Remove all other #~#

            //Substitute the #~d~# for quotes for 1_DAYS_AGO if the variable is exposed
            $value = preg_replace_callback("/'[^']*?'/", function($matches){ //Foreach group of quotes
                return str_replace("#~d~#", "", $matches[0]); //Remove the #~#
            }, $value);
            $value = str_replace("#~d~#", "'", $value);



            // If no [[ or ( expressions assume this is a single string
            if (str_replace(["(", ")"], ["", ""], preg_replace("/\[\[(.*?)\]\]/", "", $value)) == $valueEntry) {

                if (is_numeric($value)) {
                    if (floatval($value) != intval($value)) {
                        $value = floatval($value);
                    } else {
                        $value = intval($value);
                    }
                }

                $outputParameters[] = $value;
                $value = "?";
            } else {



                $candidateParams = [];
                $hasUnresolvedStrings = false;
                $sanitised = $this->sqlClauseSanitiser->sanitiseSQL($value, $candidateParams, $hasUnresolvedStrings);


                // Remove any [[ from column names and prefix with table alias if supplied
                $sanitised = preg_replace("/\[\[(.*?)\]\]/", ($tableAlias ? $tableAlias . "." : "") . $this->databaseConnection->escapeColumn("$1"), $sanitised);

                
                // Check for presence of unqualified bracket expressions as these
                // indicate literal string usage.
                $matches =[];
                if ($hasUnresolvedStrings && is_numeric(preg_match("/(^|[^a-zA-Z])\(/",$value, $matches, PREG_OFFSET_CAPTURE))) {
                    $candidateParams = [$value];
                    $sanitised = "?";
                }


                // Set value
                $value = $sanitised;

                // Splice params
                array_splice($outputParameters, sizeof($outputParameters), 0, $candidateParams);

            }

            $valueStrings[] = $value;

        }



        return join(",", $valueStrings);

    }


}