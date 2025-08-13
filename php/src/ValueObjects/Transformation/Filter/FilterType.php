<?php

namespace Kinintel\ValueObjects\Transformation\Filter;

enum FilterType {
    case eq;
    case neq;
    case null;
    case notnull;
    case gt;
    case lt;
    case gte;
    case lte;
    case startswith;
    case endswith;
    case contains;
    case like;
    case notlike;
    case bitwiseand;
    case bitwiseor;
    case similarto;
    case between;
    case in;
    case notin;


    /**
     * From string method
     *
     * @param $string
     * @return FilterType
     */
    public static function fromString($string){
        foreach (self::cases() as $status) {
            if( $string === $status->name ){
                return $status;
            }
        }
    }

}
