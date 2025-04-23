<?php

namespace Kinintel\Objects\Datasource;

use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateResultItemValidationErrors;

/**
 * External validator for validating datasource items
 */
class DatasourceDataValidator {


    /**
     * @var Field
     */
    private $idField = null;

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

            // Stash id field if we have one
            if ($field->getType() === Field::TYPE_ID)
                $this->idField = $field;
        }
    }


    /**
     * Validate an array of data items for update using the identified validation fields.
     * for efficiency reasons we doctor the passed array directly to remove
     * items which fail validation and return an array of validation errors.
     *
     * @param mixed[] $data
     * @param $updateMode
     * @return DatasourceUpdateResultItemValidationErrors[]
     */
    public function validateUpdateData(&$data, $updateMode, $pruneInvalidItems = false) {

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

                if ($this->idField){
                    $idFieldName = $this->idField->getName();
                    $idFieldHasValue = isset($dataItem[$idFieldName]);
                    if (($updateMode == UpdatableDatasource::UPDATE_MODE_ADD) && $idFieldHasValue )
                        $errors[$idFieldName] = "Value should not be supplied for $idFieldName when adding new items";
                    if (($updateMode !== UpdatableDatasource::UPDATE_MODE_ADD) && !$idFieldHasValue)
                        $errors[$idFieldName] = "Value required for $idFieldName for $updateMode of items";
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