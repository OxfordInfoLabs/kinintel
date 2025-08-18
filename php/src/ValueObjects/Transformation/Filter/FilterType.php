<?php

namespace Kinintel\ValueObjects\Transformation\Filter;

/**
 * Permitted filter types
 */
enum FilterType {

    // EQUALS
    case eq;

    // NOT EQUALS
    case neq;

    // IS NULL
    case isnull;

    // IS NOT NULL
    case isnotnull;

    // GREATER THAN
    case gt;

    // LESS THAN
    case lt;

    // GREATER THAN OR EQUAL
    case gte;

    // LESS THAN OR EQUAL
    case lte;

    // STARTS WITH
    case startswith;

    // ENDS WITH
    case endswith;

    // CONTAINS
    case contains;

    // LIKE
    case like;

    // NOT LIKE
    case notlike;

    // BITWISE AND
    case bitwiseand;

    // BITWISE OR
    case bitwiseor;

    // SIMILAR TO
    case similarto;

    // BETWEEN
    case between;

    // IN
    case in;

    // NOT IN
    case notin;

    // LEGACY VALUES - TO BE REMOVED AT SOME POINT
    case null;
    case notnull;


    /**
     * From string method
     *
     * @param $string
     * @return FilterType
     */
    public static function fromString($string) {
        foreach (self::cases() as $status) {
            if ($string === $status->name) {
                return $status;
            }
        }
    }

}
