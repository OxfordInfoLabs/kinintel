<?php


namespace Kinintel\ValueObjects\Transformation\Filter;

use Kinintel\ValueObjects\Transformation\InclusionCriteria;
use Kinintel\ValueObjects\Transformation\InclusionCriteriaType;

/**
 * Filter junction - relates a set of filters together either with and / or logic
 *
 */
class FilterJunction {

    use InclusionCriteria;

    /**
     * One of the logic constants below
     *
     * @required
     */
    protected FilterLogic $logic;


    /**
     * Direct filters involved in this junction
     *
     * @var Filter[]
     */
    protected $filters;


    /**
     * Sub filter junctions involved in this junction
     *
     * @var FilterJunction[]
     */
    protected $filterJunctions;

    /**
     * FilterJunction constructor.
     *
     * @param Filter[] $filters
     * @param FilterJunction[] $filterJunctions
     * @param FilterLogic $logic
     * @param InclusionCriteriaType $inclusionCriteria
     * @param mixed $inclusionData
     */
    public function __construct(array $filters = [], array $filterJunctions = [], FilterLogic $logic = FilterLogic::AND, ?InclusionCriteriaType $inclusionCriteria = InclusionCriteriaType::Always, mixed $inclusionData = null) {
        $this->logic = $logic;
        $this->filters = $filters;
        $this->filterJunctions = $filterJunctions;
        $this->inclusionData = $inclusionData;
        $this->inclusionCriteria = $inclusionCriteria;
    }

    /**
     * @return FilterLogic
     */
    public function getLogic() {
        return $this->logic;
    }

    /**
     * @return Filter[]
     */
    public function getFilters() {
        return $this->filters;
    }

    /**
     * @return FilterJunction[]
     */
    public function getFilterJunctions() {
        return $this->filterJunctions;
    }

    /**
     * @param FilterLogic $logic
     */
    public function setLogic($logic) {
        $this->logic = $logic;
    }

    /**
     * @param Filter[] $filters
     */
    public function setFilters($filters) {
        $this->filters = $filters;
    }

    /**
     * @param FilterJunction[] $filterJunctions
     */
    public function setFilterJunctions($filterJunctions) {
        $this->filterJunctions = $filterJunctions;
    }


}