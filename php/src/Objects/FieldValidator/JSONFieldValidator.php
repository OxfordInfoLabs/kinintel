<?php

namespace Kinintel\Objects\FieldValidator;

class JSONFieldValidator implements FieldValidator {

    /**
     * Validate JSON field value - ensure it's json encodable.
     *
     * @param $value
     * @param $field
     * @return void
     */
    public function validateValue($value, $field) {

        // Validate JSON fields.
        if ($value === null || is_object($value) || is_array($value))
            return true;

        // Attempt json_encode
        if (json_decode($value, true) === null) {
            return  "Invalid json value supplied for ".$field->getName().".  Please ensure that the data is well formed with keys and string values quoted with double quotes.";
        } else {
            return true;
        }


    }
}