<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot;

/**
 * Time lapse field set entries
 *
 * Class TimeLapseFieldSet
 * @package Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot
 */
class TimeLapseFieldSet {

    /**
     * @var int[]
     * @required
     */
    private $dayOffsets;


    /**
     * @var string[]
     * @required
     */
    private $fieldNames;

    /**
     * TimeLapseFieldSet constructor.
     * @param int[] $dayOffsets
     * @param string[] $fieldNames
     */
    public function __construct($dayOffsets = [], $fieldNames = []) {
        $this->dayOffsets = $dayOffsets;
        $this->fieldNames = $fieldNames;
    }


    /**
     * @return int[]
     */
    public function getDayOffsets() {
        return $this->dayOffsets;
    }

    /**
     * @param int[] $dayOffsets
     */
    public function setDayOffsets($dayOffsets) {
        $this->dayOffsets = $dayOffsets;
    }

    /**
     * @return string[]
     */
    public function getFieldNames() {
        return $this->fieldNames;
    }

    /**
     * @param string[] $fieldNames
     */
    public function setFieldNames($fieldNames) {
        $this->fieldNames = $fieldNames;
    }


}