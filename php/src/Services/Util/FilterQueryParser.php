<?php

namespace Kinintel\Services\Util;

use Kinintel\Exception\InvalidQueryClauseException;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterLogic;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;

class FilterQueryParser {

    const CONDITION_TOKENS = ["==" => Filter::FILTER_TYPE_EQUALS,
        "!=" => Filter::FILTER_TYPE_NOT_EQUALS,
        ">" => Filter::FILTER_TYPE_GREATER_THAN,
        ">=" => Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO,
        "<" => Filter::FILTER_TYPE_LESS_THAN,
        "<=" => Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO,
        Filter::FILTER_TYPE_CONTAINS => Filter::FILTER_TYPE_CONTAINS,
        Filter::FILTER_TYPE_STARTS_WITH => Filter::FILTER_TYPE_STARTS_WITH,
        Filter::FILTER_TYPE_ENDS_WITH => Filter::FILTER_TYPE_ENDS_WITH,
        "like" => Filter::FILTER_TYPE_LIKE,
        "likeregexp" => Filter::FILTER_TYPE_LIKE,
        "notlike" => Filter::FILTER_TYPE_NOT_LIKE,
        "notlikeregexp" => Filter::FILTER_TYPE_NOT_LIKE,
        Filter::FILTER_TYPE_IN => Filter::FILTER_TYPE_IN,
        Filter::FILTER_TYPE_NOT_IN => Filter::FILTER_TYPE_NOT_IN,
        "isnull" => Filter::FILTER_TYPE_NULL,
        "isnotnull" => Filter::FILTER_TYPE_NOT_NULL];

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


        // Now process structural elements


        $filter = $this->convertClauseToFilter($sanitised, $substitutions);

        return new FilterJunction([$filter]);

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
        $tokenised = preg_split("/ +/", $queryString, 3);

        if (sizeof($tokenised) >= 2) {

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
                if ($operator == Filter::FILTER_TYPE_LIKE || $operator == Filter::FILTER_TYPE_NOT_LIKE) {
                    $rhs = [$rhs, str_contains($tokenised[1], "regexp") ? Filter::LIKE_MATCH_REGEXP : Filter::LIKE_MATCH_WILDCARD];
                }

                // if an in clause, process this as an array of values
                if ($operator == Filter::FILTER_TYPE_IN || $operator == Filter::FILTER_TYPE_NOT_IN) {
                    $rhs = preg_split("/\W*,\W*/", trim($rhs, " []"));
                }

            }

            return new Filter($lhs, $rhs, $operator);

        } else {
            throw new InvalidQueryClauseException($queryString);
        }
    }


    // Substitute placeholder values for a string
    private function substitutePlaceholderValues($string, $placeholders) {

        return preg_replace_callback("/\?([0-9]+)/", function ($placeholder) use ($placeholders) {
            return str_replace("##APOST##", "'", $placeholders[$placeholder[1] - 1]);
        }, $string);
    }


}