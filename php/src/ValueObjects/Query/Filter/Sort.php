<?php


namespace Kinintel\ValueObjects\Query\Filter;


class Sort {

    /**
     * The field upon which to sort
     *
     * @var string
     */
    private $fieldName;

    /**
     * The direction of sort (one of constants)
     *
     * @var string
     */
    private $direction;

    // Direction constants for sort.
    const DIRECTION_ASC = "asc";
    const DIRECTION_DESC = "desc";

    /**
     * Sort constructor.
     *
     * @param string $fieldName
     * @param string $direction
     */
    public function __construct($fieldName, $direction) {
        $this->fieldName = $fieldName;
        $this->direction = $direction;
    }


    /**
     * @return string
     */
    public function getFieldName() {
        return $this->fieldName;
    }

    /**
     * @return string
     */
    public function getDirection() {
        return $this->direction;
    }


}