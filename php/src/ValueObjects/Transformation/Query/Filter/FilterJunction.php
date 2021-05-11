<?php


namespace Kinintel\ValueObjects\Transformation\Query\Filter;

/**
 * Filter junction - relates a set of filters together either with and / or logic
 *
 */
class FilterJunction {

    /**
     * One of the logic constants below
     *
     * @var string
     * @required
     */
    protected $logic;


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


    // Logic modes
    const LOGIC_AND = "AND";
    const LOGIC_OR = "OR";

    /**
     * FilterJunction constructor.
     *
     * @param Filter[] $filters
     * @param FilterJunction[] $filterJunctions
     * @param string $logic
     */
    public function __construct($filters = [], $filterJunctions = [], $logic = self::LOGIC_AND) {
        $this->logic = $logic;
        $this->filters = $filters;
        $this->filterJunctions = $filterJunctions;
    }

    /**
     * @return string
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
     * @param string $logic
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