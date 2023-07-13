<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;


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
                    if ($matches[1] || !is_numeric($matchingParamValueElement))
                        $literals[] = "'" . ($matches[1] ? "%" : "") . $matchingParamValueElement . ($matches[3] ? "%" : "") . "'";
                    else if (is_numeric($matchingParamValueElement))
                        $literals[] = $matchingParamValueElement;
                }
                return join(",", $literals);
            }, $valueEntry);

            // Evaluate time offset parameters for days ago and hours ago
            $value = preg_replace_callback("/'*([0-9]+)_YEARS_AGO'*/", function ($matches) use (&$outputParameters) {
                return "'" . (new \DateTime())->sub(new \DateInterval("P" . $matches[1] . "Y"))->format("Y-m-d H:i:s") . "'";
            }, $value);

            $value = preg_replace_callback("/'*([0-9]+)_MONTHS_AGO'*/", function ($matches) use (&$outputParameters) {
                return "'" . (new \DateTime())->sub(new \DateInterval("P" . $matches[1] . "M"))->format("Y-m-d H:i:s") . "'";
            }, $value);

            $value = preg_replace_callback("/'*([0-9]+)_DAYS_AGO'*/", function ($matches) use (&$outputParameters) {
                return "'" . (new \DateTime())->sub(new \DateInterval("P" . $matches[1] . "D"))->format("Y-m-d H:i:s") . "'";
            }, $value);

            $value = preg_replace_callback("/'*([0-9]+)_HOURS_AGO'*/", function ($matches) use (&$outputParameters) {
                return "'" . (new \DateTime())->sub(new \DateInterval("PT" . $matches[1] . "H"))->format("Y-m-d H:i:s") . "'";
            }, $value);

            $value = preg_replace_callback("/'*([0-9]+)_MINUTES_AGO'*/", function ($matches) use (&$outputParameters) {
                return "'" . (new \DateTime())->sub(new \DateInterval("PT" . $matches[1] . "M"))->format("Y-m-d H:i:s") . "'";
            }, $value);

            $value = preg_replace_callback("/'*([0-9]+)_SECONDS_AGO'*/", function ($matches) use (&$outputParameters) {
                return "'" . (new \DateTime())->sub(new \DateInterval("PT" . $matches[1] . "S"))->format("Y-m-d H:i:s") . "'";
            }, $value);


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
                $sanitised = $this->sqlClauseSanitiser->sanitiseSQL($value, $candidateParams);

                // Remove any [[ from column names and prefix with table alias if supplied
                $sanitised = preg_replace("/\[\[(.*?)\]\]/", ($tableAlias ? $tableAlias . "." : "") . $this->databaseConnection->escapeColumn("$1"), $sanitised);

                // Check for presence of unqualified bracket expressions as these
                // indicate literal string usage.
                $unqualifiedBrackets = preg_replace("/(^|[^a-zA-Z])\([^?\"]*?(\)|$)/", "", $sanitised);
                if ($unqualifiedBrackets <> $sanitised) {
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