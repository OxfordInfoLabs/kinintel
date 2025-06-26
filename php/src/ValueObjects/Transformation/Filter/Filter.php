<?php


namespace Kinintel\ValueObjects\Transformation\Filter;


use Kinikit\Core\Util\Primitive;
use Kinintel\ValueObjects\Transformation\InclusionCriteria;
use Kinintel\ValueObjects\Transformation\InclusionCriteriaType;

class Filter {

    use InclusionCriteria;

    /**
     * LHS expression for filter
     *
     * @var mixed
     */
    private $lhsExpression;


    /**
     * RHS expression for filter
     *
     * @var mixed
     */
    private $rhsExpression;


    /**
     * Filter type - one of constants
     *
     * @var string
     */
    private $filterType;


    // Filter type constants (single valued)
    const FILTER_TYPE_EQUALS = "eq";
    const FILTER_TYPE_NOT_EQUALS = "neq";
    const FILTER_TYPE_NULL = "null";
    const FILTER_TYPE_NOT_NULL = "notnull";
    const FILTER_TYPE_GREATER_THAN = "gt";
    const FILTER_TYPE_LESS_THAN = "lt";
    const FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO = "gte";
    const FILTER_TYPE_LESS_THAN_OR_EQUAL_TO = "lte";
    const FILTER_TYPE_STARTS_WITH = "startswith";
    const FILTER_TYPE_ENDS_WITH = "endswith";
    const FILTER_TYPE_CONTAINS = "contains";
    const FILTER_TYPE_LIKE = "like";
    const FILTER_TYPE_NOT_LIKE = "notlike";
    const FILTER_TYPE_BITWISE_AND = "bitwiseand";
    const FILTER_TYPE_BITWISE_OR = "bitwiseor";


    // Filter type constants (multi valued)
    const FILTER_TYPE_SIMILAR_TO = "similarto";
    const FILTER_TYPE_BETWEEN = "between";
    const FILTER_TYPE_IN = "in";
    const FILTER_TYPE_NOT_IN = "notin";

    // Like match types
    const LIKE_MATCH_WILDCARD = "likewildcard";
    const LIKE_MATCH_REGEXP = "likeregexp";



    /**
     * Filter constructor.
     *
     * @param string $lhsExpression
     * @param mixed $rhsExpression
     * @param string $filterType
     * @poram InclusionCriteriaType $inclusionCriteria
     * @param string $inclusionData
     */
    public function __construct(mixed $lhsExpression, mixed $rhsExpression, ?string $filterType = null, ?InclusionCriteriaType $inclusionCriteria = InclusionCriteriaType::Always, mixed $inclusionData = null) {
        $this->lhsExpression = $lhsExpression;
        $this->rhsExpression = $rhsExpression;

        if (!$filterType) {
            if (Primitive::isPrimitive($rhsExpression)) {
                $filterType = is_numeric(strpos($rhsExpression, "*")) ? self::FILTER_TYPE_LIKE : self::FILTER_TYPE_EQUALS;
            } else if (is_array($rhsExpression)) {
                $filterType = self::FILTER_TYPE_IN;
            }
        }

        $this->filterType = $filterType;
        $this->inclusionCriteria = $inclusionCriteria;
        $this->inclusionData = $inclusionData;

    }

    /**
     * @return mixed
     */
    public function getLhsExpression() {
        return $this->lhsExpression;
    }

    /**
     * @return mixed
     */
    public function getRhsExpression() {
        return $this->rhsExpression;
    }

    /**
     * @return string
     */
    public function getFilterType() {
        return $this->filterType;
    }




}