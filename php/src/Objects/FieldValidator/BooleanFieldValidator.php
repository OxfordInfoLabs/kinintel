<?php

namespace Kinintel\Objects\FieldValidator;

class BooleanFieldValidator implements FieldValidator {

    /**
     * Validate a boolean field
     *
     * @param $value
     * @param $field
     * @return void
     */
    public function validateValue($value, $field) {

        // Allow blanks
        if ($value === null)
            return true;

        $valid = is_bool($value);

        return $valid ?: "Invalid boolean value supplied for " . $field->getName();

    }
}