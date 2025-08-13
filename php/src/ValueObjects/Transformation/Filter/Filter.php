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

        // If no filter type, do the sensible thing.
        if (!$filterType) {
            if (Primitive::isPrimitive($rhsExpression)) {
                $filterType = is_numeric(strpos($rhsExpression, "*"))
                || (str_starts_with($rhsExpression, "/") && str_ends_with($rhsExpression, "/")) ? FilterType::like : FilterType::eq;
            } else if (is_array($rhsExpression)) {
                $filterType = FilterType::in;
            }
        }

        // If a like filter type without an array, determine the like match type.
        if (($filterType == FilterType::like || $filterType == FilterType::notlike) && is_string($rhsExpression)) {
            $evaluatedRHS = trim($rhsExpression, "/");
            $rhsExpression = [$evaluatedRHS, ($evaluatedRHS == $rhsExpression) ? Filter::LIKE_MATCH_WILDCARD : Filter::LIKE_MATCH_REGEXP];
        }

        // If a null type filter the RHS should be nullified
        if ($filterType == FilterType::null || $filterType == FilterType::isnull || $filterType == FilterType::notnull || $filterType == FilterType::isnotnull)
            $rhsExpression = null;

        // If an in or a not in filter and supplied as a string, upgrade to an array using CSV parser for enclosure safety
        if (($filterType == FilterType::in || $filterType == FilterType::notin) && is_string($rhsExpression)) {
            $rhsExpression = str_getcsv($rhsExpression, ",", '"');
        }

        $this->rhsExpression = $rhsExpression;
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


    /**
     * Create an array of Filter objects from an indexed array of values keyed in
     * by a field and a type in format fieldname_type
     *
     * @param array $array
     * @return Filter[]
     */
    public static function createFiltersFromFieldTypeIndexedArray(array $array): array {

        // Loop through the array and map to filters
        $filters = [];
        foreach ($array as $key => $value) {
            $splitKey = explode("_", $key);

            $filterType = trim(array_pop($splitKey));
            $fieldName = trim(join("_", $splitKey));

            // If field name and a filter type which matches
            if ($fieldName && $filterType = FilterType::fromString($filterType)) {
                $filters[] = new Filter("[[" . $fieldName . "]]", $value, $filterType);
            }

        }

        return $filters;

    }


}