<?php

namespace Kinintel\Objects\Datasource;

use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateResultItemValidationErrors;

/**
 * External validator for validating datasource items
 */
class DatasourceDataValidator {

    /**
     * @var DatasourceUpdateField[]
     */
    private $validationFields = [];

    /**
     * Construct with an array of validation fields
     *
     * @param array $fields
     */
    public function __construct(?array $fields = []) {
        foreach ($fields ?? [] as $field) {
            if (($field instanceof DatasourceUpdateField) && sizeof($field->returnValidators()))
                $this->validationFields[] = $field;
        }
    }


    /**
     * Validate an array of data items for update using the identified validation fields.
     * for efficiency reasons we doctor the passed array directly to remove
     * items which fail validation and return an array of validation errors.
     *
     * @param mixed[] $data
     * @return DatasourceUpdateResultItemValidationErrors[]
     */
    public function validateUpdateData(&$data, $pruneInvalidItems = false) {

        $validationErrors = [];

        if (sizeof($this->validationFields)) {

            // Run through the items
            for ($i = sizeof($data) - 1; $i >= 0; $i--) {
                $dataItem = $data[$i];
                $errors = [];
                foreach ($this->validationFields as $validationField) {
                    $fieldName = $validationField->getName();
                    $validation = $validationField->validateValue($dataItem[$fieldName] ?? null);
                    if ($validation !== true) {
                        $errors[$fieldName] = $validation;
                    }
                }

                // If validation errors,
                if (sizeof($errors)) {
                    if ($pruneInvalidItems)
                        array_splice($data, $i, 1);
                    $validationErrors[] = new DatasourceUpdateResultItemValidationErrors($i, $errors);
                }
            }
        }

        $validationErrors = array_reverse($validationErrors);

        return $validationErrors;
    }


}