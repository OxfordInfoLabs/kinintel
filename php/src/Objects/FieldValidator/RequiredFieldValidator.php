<?php

namespace Kinintel\Objects\FieldValidator;

use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;

/**
 * Required field validation rule.  Simply checks that passed values are
 * neither null or blank.
 */
class RequiredFieldValidator implements FieldValidator {

    /**
     * @param $value
     * @param DatasourceUpdateField $field
     *
     * @return bool|string
     */
    public function validateValue($value, $field) {
        if ($value === null | $value === "") {
            return "Value required for " . $field->getName();
        }
        return true;
    }
}