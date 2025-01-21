<?php

namespace Kinintel\Objects\FieldValidator;

use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;

class DateFieldValidator implements FieldValidator {

    const DATE_FORMAT = "Y-m-d";
    const DATE_TIME_FORMAT = "Y-m-d H:i:s";

    public function __construct(private bool $includeTime = false) {
    }

    /**
     * @param mixed $value
     * @param DatasourceUpdateField $field
     *
     * @return bool|string
     */
    public function validateValue($value, $field) {

        // Allow blanks
        if ($value === null || $value === "")
            return true;

        $date = date_create_from_format($this->includeTime ? self::DATE_TIME_FORMAT : self::DATE_FORMAT, $value);
        if ($date) {
            return true;
        } else {
            return "Invalid date " . ($this->includeTime ? "time " : "") . "value supplied for " . $field->getName();
        }
    }

    /**
     * @return bool
     */
    public function isIncludeTime(): bool {
        return $this->includeTime;
    }

    /**
     * @param bool $includeTime
     */
    public function setIncludeTime(bool $includeTime): void {
        $this->includeTime = $includeTime;
    }


}