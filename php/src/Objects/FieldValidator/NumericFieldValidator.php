<?php

namespace Kinintel\Objects\FieldValidator;

use Kinikit\Core\Logging\Logger;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;

class NumericFieldValidator implements FieldValidator {

    public function __construct(private bool $allowDecimals = true) {
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
        if ($value === null || $value === "")
            return true;

        $valid = is_numeric($value) && ($this->allowDecimals || floatval($value) == intval($value));

        $typeString = $this->allowDecimals ? "numeric" : "integer";

        return $valid ?: "Invalid " . $typeString . " value supplied for " . $field->getName();

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