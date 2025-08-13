<?php

namespace Kinintel\Services\Util;

use Kinintel\Exception\AmbiguousQueryLogicException;
use Kinintel\Exception\InvalidQueryClauseException;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterLogic;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;

class FilterQueryParser {

    const CONDITION_TOKENS = ["==" => FilterType::eq,
        "!=" =>FilterType::neq,
        ">" => FilterType::gt,
        ">=" => FilterType::gte,
        "<" => FilterType::lt,
        "<=" => FilterType::lte,
        "contains" => FilterType::contains,
        "startswith" => FilterType::startswith,
        "endswith" => FilterType::endswith,
        "like" => FilterType::like,
        "likeregexp" => FilterType::like,
        "notlike" => FilterType::notlike,
        "notlikeregexp" => FilterType::notlike,
        "in" => FilterType::in,
        "notin"=> FilterType::notin,
        "isnull" => FilterType::null,
        "isnotnull" => FilterType::notnull];

    const LOGIC_TOKENs = [
        "&&" => FilterLogic::AND,
        "||" => FilterLogic::OR
    ];


    /**
     * Convert a query string to a filter junction
     *
     * @param $queryString
     * @return FilterTransformation
     */
    public function convertQueryToFilterJunction($queryString) {


        // Sort out nested apostrophes up front.
        $sanitised = str_replace("\\'", "##APOST##", $queryString);

        // Substitutions array applied next
        $substitutions = [];

        // Extract content items first
        $sanitised = preg_replace_callback("/[0-9'\"].*?[0-9'\"]/", function ($matches) use (&$substitutions) {
            $substitutions[] = trim($matches[0], ' \'"');
            return "?" . sizeof($substitutions);
        }, $sanitised);


        $sanitised = preg_replace_callback("/\[.*?\]/", function ($matches) use (&$substitutions) {
            $substitutions[] = $matches[0];
            return "?" . sizeof($substitutions);
        }, $sanitised);

        // Now process structural elements
        return $this->convertClauseToFilterJunction($sanitised, $substitutions);

    }


    /**
     * Convert clause to a filter junction.
     *
     * @param string $queryString
     * @param array $sustitutions
     * @return FilterJunction
     */
    private function convertClauseToFilterJunction(string $queryString, array $substitutions): FilterJunction {

        do {
            $origQueryString = $queryString;
            $queryString = preg_replace_callback("/\(([^\(]*?)\)/", function ($matches) use (&$substitutions) {
                $substitutions[] = $this->convertClauseToFilterJunction($matches[1], $substitutions);
                return "$" . sizeof($substitutions);
            }, $queryString);
        } while ($queryString !== $origQueryString);

        $andMatches = preg_split("/\W&&\W/", $queryString, -1);
        $orMatches = preg_split("/\W\|\|\W/", $queryString, -1);

        if (sizeof($orMatches) > 1 && sizeof($andMatches) > 1)
            throw new AmbiguousQueryLogicException($queryString);

        // Work out which logic to apply and to which items
        $junctionLogic = sizeof($orMatches) > 1 ? FilterLogic::OR : FilterLogic::AND;
        $junctionItems = sizeof($orMatches) > 1 ? $orMatches : $andMatches;

        $filters = [];
        $filterJunctions = [];
        foreach ($junctionItems as $item) {
            $trimmed = trim($item);
            if (str_starts_with($trimmed, "$"))
                $filterJunctions[] = $substitutions[substr($trimmed, 1) - 1];
            else
                $filters[] = $this->convertClauseToFilter($item, $substitutions);
        }


        // Return new filter junction
        return new FilterJunction($filters, $filterJunctions, $junctionLogic);

    }


    /**
     * Convert a single clause into a filter
     *
     * @param $queryString
     * @param array $substitutions
     *
     * @return Filter
     * @throws InvalidQueryClauseException
     */
    private function convertClauseToFilter(string $queryString, array $substitutions): Filter {

        // Explode the expression on whitespace firstly
        $tokenised = preg_split("/ +/", $queryString);

        if (sizeof($tokenised) >= 2 && sizeof($tokenised) < 4) {

            $lhs = str_contains($tokenised[0], "?") ? $this->substitutePlaceholderValues($tokenised[0], $substitutions) :
                (!is_numeric($tokenised[0]) ? "[[" . $tokenised[0] . "]]" : $tokenised[0]);

            $operator = self::CONDITION_TOKENS[$tokenised[1]] ?? null;
            if (!$operator)
                throw new InvalidQueryClauseException($queryString);

            $rhs = null;
            if ($tokenised[2] ?? null) {

                $rhs = str_contains($tokenised[2], "?") ? $this->substitutePlaceholderValues($tokenised[2], $substitutions) :
                    (!is_numeric($tokenised[2]) ? "[[" . $tokenised[2] . "]]" : $tokenised[2]);

                // Handle the like cases to convert into an array structure.
                if ($operator == FilterType::like || $operator == FilterType::notlike) {
                    $rhs = [$rhs, str_contains($tokenised[1], "regexp") ? Filter::LIKE_MATCH_REGEXP : Filter::LIKE_MATCH_WILDCARD];
                }

                // if an in clause, process this as an array of values
                if ($operator == FilterType::in || $operator == FilterType::notin) {
                    $rhs = preg_split("/\W*,\W*/", trim($rhs, " []"));
                }

            }

            return new Filter($lhs, $rhs, $operator);

        } else {
            throw new InvalidQueryClauseException($queryString);
        }
    }


    // Substitute placeholder values for a string - perform 2 rounds to allow for recursive placeholders
    private function substitutePlaceholderValues($string, $placeholders) {

        do {
            $origString = $string;
            $string = preg_replace_callback("/\?([0-9]+)/", function ($placeholder) use ($placeholders) {
                return str_replace("##APOST##", "'", $placeholders[$placeholder[1] - 1]);
            }, $string);

        } while ($string !== $origString);

        return $string;
    }


}