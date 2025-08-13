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
     * @var FilterType
     */
    private $filterType;


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
    public function __construct(mixed $lhsExpression, mixed $rhsExpression, ?FilterType $filterType = null, ?InclusionCriteriaType $inclusionCriteria = InclusionCriteriaType::Always, mixed $inclusionData = null) {
        $this->lhsExpression = $lhsExpression;
        $this->rhsExpression = $rhsExpression;

        if (!$filterType) {
            if (Primitive::isPrimitive($rhsExpression)) {
                $filterType = is_numeric(strpos($rhsExpression, "*")) ? FilterType::like : FilterType::eq;
            } else if (is_array($rhsExpression)) {
                $filterType = FilterType::in;
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
     * @return FilterType
     */
    public function getFilterType() {
        return $this->filterType;
    }


}