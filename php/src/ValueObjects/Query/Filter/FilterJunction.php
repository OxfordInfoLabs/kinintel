<?php


namespace Kinintel\ValueObjects\Query\Filter;

/**
 * Filter junction - relates a set of filters together either with and / or logic
 *
 */
class FilterJunction {

    /**
     * One of the logic constants below
     *
     * @var string
     */
    private $logic;


    /**
     * Either Filter or nested FilterJunctions
     *
     * @var mixed
     */
    private $filterObjects;


    // Logic modes
    const LOGIC_AND = "and";
    const LOGIC_OR = "or";

    /**
     * FilterJunction constructor.
     *
     * @param mixed $filterObjects
     * @param string $logic
     */
    public function __construct($filterObjects, $logic = self::LOGIC_AND) {
        $this->logic = $logic;
        $this->filterObjects = $filterObjects;
    }

    /**
     * @return string
     */
    public function getLogic() {
        return $this->logic;
    }

    /**
     * @return mixed
     */
    public function getFilterObjects() {
        return $this->filterObjects;
    }


}