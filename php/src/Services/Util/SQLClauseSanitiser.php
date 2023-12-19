<?php


namespace Kinintel\Services\Util;

/**
 * Class SQLClauseSanitiser
 * @package Kinintel\Services\Util
 *
 * @noProxy
 */
class SQLClauseSanitiser {

    const NUMERIC_WHITELISTED_FUNCTION = "NUMERIC";
    const STRING_WHITELISTED_FUNCTION = "STRING";
    const DATE_WHITELISTED_FUNCTION = "DATE";
    const ANY_WHITELISTED_FUNCTION = "ANY";
    const AGGREGATE_FUNCTION = "AGGREGATE";


    private $whiteListedSymbols = [
        "+",
        "-",
        "*",
        "/",
        "%",
        "|",
        "=",
        ">",
        "<",
        "!",
        "(",
        ")",
        "?",
        "$",
        ","
    ];

    private $whitelistedKeywords = [
        "AND",
        "BETWEEN",
        "CASE",
        "ELSE",
        "END",
        "EXISTS",
        "IN",
        "IS",
        "ISNULL",
        "LIKE",
        "NOT",
        "NOTNULL",
        "NULL",
        "OR",
        "THEN",
        "WHEN",
        "ASC",
        "DESC"
    ];

    // List of white listed functions along with parameter info
    private $whitelistedFunctions = [
        "ABS" => ["params" => ["X"],
            "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Return the absolute value of numeric argument X"],
        "AVG" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the average value of all non-null values of X within the group"],
        "CEILING" => ["params" => ["X"],
            "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Return the closest integer above the supplied numeric argument X"],
        "COALESCE" => ["params" => ["X", "Y", "..."], "category" => self::ANY_WHITELISTED_FUNCTION,
            "description" => "Return the first non-null value from the supplied parameter list"],
        "CONCAT" => ["params" => ["X", "Y", "..."], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Join all passed strings together and return the combined result"],
        "COUNT" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the count of all matching values in the group"],
        "COUNT_TOTAL" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the total count for the whole data set"],
        "COUNT_PERCENT" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the count of all matching values in the group as a percentage of total rows"],
        "DISTINCT" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the distinct values of X in the group or result set"],
        "DOT_PRODUCT" => ["params" => ["X", "Y"],
            "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Calculate the dot product of 2 vectors in standard embedding format"],
        "EXP" => ["params" => ["X"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Return e (the Euler constant) to the power of X"],
        "FLOOR" => ["params" => ["X"],
            "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Return the closest integer below the supplied numeric argument X"],
        "GREATEST" => ["params" => ["X", "Y", "..."], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Return the largest value of all supplied arguments"],
        "GROUP_CONCAT" => ["params" => ["X"], "category" => self::AGGREGATE_FUNCTION,
            "description" => "Join together the values of the expression identified as X with commas"],
        "IFNULL" => ["params" => ["X", "Y"], "category" => self::ANY_WHITELISTED_FUNCTION,
            "description" => "Return the first argument if not null or second argument if not null or null if both are null"],
        "INSTR" => ["params" => ["X", "Y"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Return the position of the string supplied as Y within the string supplied as X"],
        "LEAST" => ["params" => ["X", "Y", "..."], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Return the smallest value of all supplied arguments"],
        "LENGTH" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Return the length of the string argument supplied"],
        "LN" => ["params" => ["X"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns the natural logarithm of X"],
        "LOG" => ["params" => ["B", "X"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns the logarithm of X base B"],
        "LOWER" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Convert the supplied string to lower case"],
        "LTRIM" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Remove whitespace from start of the supplied string"],
        "MAX" => ["params" => ["X"], "category" => self::AGGREGATE_FUNCTION,
            "description" => "Returns maximum of all supplied values for the expression X in the group"],
        "MIN" => ["params" => ["X"], "category" => self::AGGREGATE_FUNCTION,
            "description" => "Returns minimum of all supplied  values for the expression X in the group"],
        "PERCENT" => ["params" => ["X"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns the percentage of X of the sum of all values of X"],
        "POW" => ["params" => ["X", "Y"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns X raised to the power of Y"],
        "REPLACE" => ["params" => ["X", "Y", "Z"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Replaces all occurrences of the string passed as Y with the string passed as Z in the string X"],
        "ROUND" => ["params" => ["X", "Y"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Rounds the supplied number X to number of decimal places supplied as Y (defaults to 0 dp)"],
        "ROW_COUNT" => ["params" => [], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns the number of rows in the current data set"],
        "ROW_NUMBER" => ["params" => ["X", "Y"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns the sequential index of the current row in the current data set.  The arguments X and Y define criteria for sorting the rows with an optional direction e.g. [[mycolumn]] DESC or [[othercolumn]] ASC"],
        "RTRIM" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Remove whitespace from end of the supplied string"],
        "SQRT" => ["params" => ["X"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns the square root of X"],
        "SUBSTR" => ["params" => ["X", "Y", "Z"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Return the portion of the string passed as the first argument, starting at Y and extracting Z number of characters"],
        "SUM" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the sum of all matching values of X in the group"],
        "SUM_TOTAL" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the total sum of all matching values of X for the whole data set"],
        "SUM_PERCENT" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the sum of all matching values of X in the group as a percentage of the total sum for the whole data set"],
        "TRIM" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Removes whitespace from start and end of the supplied string"],
        "TOTAL" => ["params" => ["X"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns the sum of all values for X for the whole dataset"],
        "UPPER" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Convert the supplied string to upper case"],
        "DAY" => ["params" => ["X"], "category" => self::DATE_WHITELISTED_FUNCTION,
            "description" => "Extract the day of the month from a date"],
        "MONTH" => ["params" => ["X"], "category" => self::DATE_WHITELISTED_FUNCTION,
            "description" => "Extract the numerical month value from a date"],
        "YEAR" => ["params" => ["X"], "category" => self::DATE_WHITELISTED_FUNCTION,
            "description" => "Extract the numerical year value from a date"],
        "NOW" => ["params" => [], "category" => self::DATE_WHITELISTED_FUNCTION,
            "description" => "Return the current data and time"],
        "EPOCH_SECONDS" => ["params" => ["X"], "category" => self::DATE_WHITELISTED_FUNCTION,
            "description" => "Convert a date into seconds since 01/01/1970"]
    ];

    /**
     * Sanitise SQL with any existing parameter values.
     * This will return a cleaned version of the SQL which only allows the whitelisted
     * functions and parameterises any quoted strings or numbers.
     *
     * The parameterValues array will be updated by reference to contain all parameters
     * in correct order to satisfy the sanitised SQL.
     *
     * @return string
     */
    public function sanitiseSQL($sqlString, &$parameterValues = [], &$hasUnresolvedStrings = false) {

        // Copy params
        $existingParams = $parameterValues;
        $parameterValues = array();
        $columnNames = array();

        // Look for columns, literals or existing ? values
        $sqlString = preg_replace_callback("/(\[\[[a-zA-Z0-9\-_]*?\]\]|'.*?'|[0-9\.]+|\?)/", function ($matches) use (&$parameterValues, &$existingParams, &$columnNames) {
            $literal = $matches[0];

            //If it's inside square brackets
            if (trim($literal, "[]") != $literal) {
                $columnNames[] = $literal;
                return "$" . (sizeof($columnNames) - 1);
            }

            //Otherwise it's a literal
            $literal = trim($literal, "'");

            // If a numerical value passed, ensure we cast accordingly
            if (is_numeric($literal)) {
                if (floatval($literal) != intval($literal)) {
                    $literal = floatval($literal);
                } else {
                    $literal = intval($literal);
                }
            }

            if ($literal === "?") {
                $parameterValues[] = array_shift($existingParams);
            } else {
                $parameterValues[] = $literal;
            }
            return "?";
        }, $sqlString);


        // Remove any other [[ ]] expressions.
        $sqlString = preg_replace("/\[\[.*?\]\]/", "", $sqlString);

        // Remove any keywords which don't match our whitelisted ones
        $sqlString = preg_replace_callback("/\\w+/", function ($matches) use (&$hasUnresolvedStrings) {
            $targetKeyword = strtoupper($matches[0]);
            if (!is_numeric($targetKeyword)) {
                if (isset($this->whitelistedFunctions[$targetKeyword]) || in_array($targetKeyword, $this->whitelistedKeywords)) {
                    return $matches[0];
                } else {
                    $hasUnresolvedStrings = true;
                    return "";
                }
            }
            return $matches[0];
        }, $sqlString);


        // Remove any symbols which don't match our whitelisted ones
        $sqlString = preg_replace_callback("/\\W/", function ($matches) {
            return $matches[0] == " " || in_array($matches[0], $this->whiteListedSymbols) ? $matches[0] : "";
        }, $sqlString);


        // Re-add the column names back in at the end reverse mapping
        for ($index = sizeof($columnNames) - 1; $index >= 0; $index--) {
            $columnName = $columnNames[$index];
            $sqlString = str_replace("$" . $index, $columnName, $sqlString);
        }


        return $sqlString;

    }


    /**
     * Add a whitelisted function to the list (usually injected via Bootstrap)
     *
     * @param string $name
     * @param string $category
     * @param string $description
     * @param array $params
     */
    public function addWhitelistedFunction($name, $category, $description, $params = []) {
        $this->whitelistedFunctions[$name] = [
            "params" => $params,
            "category" => $category,
            "description" => $description
        ];
    }


    /**
     * Get current set of whitelisted functions
     *
     * @return array[]
     */
    public function getWhitelistedFunctions() {
        return $this->whitelistedFunctions;
    }

}
