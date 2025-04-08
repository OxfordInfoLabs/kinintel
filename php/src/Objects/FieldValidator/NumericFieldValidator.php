<?php

namespace Kinintel\Objects\FieldValidator;

use Kinikit\Core\Logging\Logger;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;

class NumericFieldValidator implements FieldValidator {


    public function __construct(private bool $allowDecimals = true, private ?float $minimumValue = null, private ?float $maximumValue = null) {
    }

    /**
     * Validate numeric fields to ensure they are numeric
     *
     * @param mixed $value
     * @param DatasourceUpdateField $field
     *
     * @return bool|string
     */
    public function validateValue($value, $field) {

        // Allow blanks
        if ($value === null)
            return true;

        $valid = is_numeric($value) && ($this->allowDecimals || floatval($value) == intval($value));

        $typeString = $this->allowDecimals ? "numeric" : "integer";

        // If not valid type, return immediately
        if (!$valid) return "Invalid " . $typeString . " value supplied for " . $field->getName();

        if (!is_null($this->minimumValue) && is_null($this->maximumValue) && ($value < $this->minimumValue))
            return "Invalid value supplied for " . $field->getName().".  Must be greater than or equal to ".$this->minimumValue;

        if (!is_null($this->maximumValue) && is_null($this->minimumValue) && ($value > $this->maximumValue))
            return "Invalid value supplied for " . $field->getName().".  Must be less than or equal to ".$this->maximumValue;

        if (!is_null($this->maximumValue) && !is_null($this->minimumValue) && ($value < $this->minimumValue || $value > $this->maximumValue))
            return "Invalid value supplied for " . $field->getName().".  Must be between ".$this->minimumValue." and ".$this->maximumValue;


        return true;
    }

    /**
     * @return bool
     */
    public function isAllowDecimals(): bool {
        return $this->allowDecimals;
    }

    /**
     * @param bool $allowDecimals
     */
    public function setAllowDecimals(bool $allowDecimals): void {
        $this->allowDecimals = $allowDecimals;
    }


}