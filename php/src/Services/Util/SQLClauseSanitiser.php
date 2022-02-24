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
        "WHEN"
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
        "COUNT" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the count of all matching values in the group"],
        "DISTINCT" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the distinct values of X in the group or result set"],
        "FLOOR" => ["params" => ["X"],
            "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Return the closest integer below the supplied numeric argument X"],
        "IFNULL" => ["params" => ["X", "Y"], "category" => self::ANY_WHITELISTED_FUNCTION,
            "description" => "Return the first argument if not null or second argument if not null or null if both are null"],
        "LENGTH" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Return the length of the string argument supplied"],
        "LOWER" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Convert the supplied string to lower case"],
        "LTRIM" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Remove whitespace from start of the supplied string"],
        "MAX" => ["params" => ["X", "Y", "..."], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns maximum of all supplied numeric values"],
        "MIN" => ["params" => ["X", "Y", "..."], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Returns minimum of all supplied numeric values"],
        "REPLACE" => ["params" => ["X", "Y", "Z"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Replaces all occurrences of the string passed as Y with the string passed as Z in the string X"],
        "ROUND" => ["params" => ["X", "Y"], "category" => self::NUMERIC_WHITELISTED_FUNCTION,
            "description" => "Rounds the supplied number X to number of decimal places supplied as Y (defaults to 0 dp)"],
        "RTRIM" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Remove whitespace from end of the supplied string"],
        "SUBSTRING" => ["params" => ["X", "Y", "Z"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Return the portion of the string passed as the first argument, starting at Y and extracting Z number of characters"],
        "SUM" => ["params" => ["X"],
            "category" => self::AGGREGATE_FUNCTION,
            "description" => "Return the sum of all matching values of X in the group"],
        "TRIM" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Removes whitespace from start and end of the supplied string"],
        "UPPER" => ["params" => ["X"], "category" => self::STRING_WHITELISTED_FUNCTION,
            "description" => "Convert the supplied string to upper case"]
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
    public function sanitiseSQL($sqlString, &$parameterValues = []) {

        // Copy params
        $existingParams = $parameterValues;
        $parameterValues = array();
        $columnNames = array();

        // Look for literals or existing ? values
        $sqlString = preg_replace_callback("/(\[\[.*?\]\]|'.*?'|[0-9\.]+|\?)/", function ($matches) use (&$parameterValues, &$existingParams, &$columnNames) {
            $literal = trim($matches[0], "' ");
            if (trim($literal, "[]") != $literal) {
                $columnNames[] = $literal;
                return "$" . (sizeof($columnNames) - 1);
            } else if ($literal == "?") {
                $parameterValues[] = array_shift($existingParams);
            } else {
                $parameterValues[] = $literal;
            }
            return "?";
        }, $sqlString);


        // Remove any keywords which don't match our whitelisted ones
        $sqlString = preg_replace_callback("/\\w+/", function ($matches) {
            $targetKeyword = strtoupper($matches[0]);
            if (!is_numeric($targetKeyword)) {
                if (isset($this->whitelistedFunctions[$targetKeyword]) || in_array($targetKeyword, $this->whitelistedKeywords)) {
                    return $matches[0];
                } else {
                    return "";
                }
            }
            return $matches[0];
        }, $sqlString);

        // Remove any symbols which don't match our whitelisted ones
        $sqlString = preg_replace_callback("/\\W/", function ($matches) {
            return $matches[0] == " " || in_array($matches[0], $this->whiteListedSymbols) ? $matches[0] : "";
        }, $sqlString);


        // Re-add the column names back in at the end.
        foreach ($columnNames as $index => $columnName) {
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

}