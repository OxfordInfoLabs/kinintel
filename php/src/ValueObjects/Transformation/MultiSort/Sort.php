<?php


namespace Kinintel\ValueObjects\Transformation\MultiSort;


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

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }

    /**
     * @param string $direction
     */
    public function setDirection($direction) {
        $this->direction = $direction;
    }


    /**
     * Get the sort string
     *
     * @return string
     */
    public function getSortString() {
        return $this->fieldName . " " . $this->direction;
    }
}