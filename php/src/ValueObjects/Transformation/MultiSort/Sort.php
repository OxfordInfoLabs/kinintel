<?php


namespace Kinintel\ValueObjects\Transformation\MultiSort;


use Kinintel\ValueObjects\Transformation\InclusionCriteria;
use Kinintel\ValueObjects\Transformation\InclusionCriteriaType;

class Sort {

    use InclusionCriteria;

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
    public function __construct($fieldName, $direction, ?InclusionCriteriaType $inclusionCriteria = InclusionCriteriaType::Always, mixed $inclusionData = null) {
        $this->fieldName = $fieldName;
        $this->direction = $direction;
        $this->inclusionCriteria = $inclusionCriteria;
        $this->inclusionData = $inclusionData;
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

}