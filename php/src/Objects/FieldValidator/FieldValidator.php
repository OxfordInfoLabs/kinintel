<?php

namespace Kinintel\Objects\FieldValidator;


use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;

/**
 * @implementation date \Kinintel\Objects\FieldValidator\DateFieldValidator
 * @implementation numeric \Kinintel\Objects\FieldValidator\NumericFieldValidator
 * @implementation pickfrom \Kinintel\Objects\FieldValidator\PickFromSourceFieldValidator
 * @implementation required \Kinintel\Objects\FieldValidator\RequiredFieldValidator
 *
 */
interface FieldValidator {

    /**
     * Validate a value for this rule.  Returns either
     * true if the value is valid or an error string if not valid.
     *
     * @param mixed $value
     * @param DatasourceUpdateField $field
     *
     * @return bool|string
     */
    public function validateValue($value, $field);

}