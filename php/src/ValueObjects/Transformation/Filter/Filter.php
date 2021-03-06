<?php


namespace Kinintel\ValueObjects\Transformation\Filter;


use Kinikit\Core\Util\Primitive;

class Filter {

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
    const FILTER_TYPE_LIKE = "like";

    // Filter type constants (multi valued)
    const FILTER_TYPE_BETWEEN = "between";
    const FILTER_TYPE_IN = "in";
    const FILTER_TYPE_NOT_IN = "notin";

    /**
     * Filter constructor.
     *
     * @param string $lhsExpression
     * @param mixed $rhsExpression
     * @param string $filterType
     */
    public function __construct($lhsExpression, $rhsExpression, $filterType = null) {
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