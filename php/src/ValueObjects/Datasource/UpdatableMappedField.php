<?php


namespace Kinintel\ValueObjects\Datasource;


/**
 * Maps a field contained in a datasource update operation to another datasource
 * for nested update of deep datastructures
 *
 * @package Kinintel\ValueObjects\Datasource
 */
class UpdatableMappedField {

    /**
     * The name of the field being mapped to the other datasource
     *
     * @var string
     */
    private $fieldName;

    /**
     * The key of the datasource to map to
     *
     * @var string
     */
    private $datasourceInstanceKey;


    /**
     * An optional array of filters to apply to the parent dataset before using a given
     * parent row for this mapping - useful if only some rows are required to be child mapped.
     *
     * @var string[string]
     */
    private $parentFilters;
    

    /**
     * An array of mapped fields to synchronise from the parent dataset to the child datasource.
     *
     * @var string[string]
     */
    private $parentFieldMappings;


    /**
     * An array of constant field values to be simply merged into the child datasource.
     *
     * @var string[string]
     */
    private $constantFieldValues;


    /**
     * This property is used where the input values for the mapping are literal values
     * rather than objects so we need to create a wrapper object with a single property
     * named using the target field name.
     *
     * @var string
     */
    private $targetFieldName;


    /**
     * If target field is being used (i.e. the input values are single value string literals)
     * if this is set to true, the field will also be retained in the parent
     * datasource.
     *
     * @var bool
     */
    private $retainTargetFieldInParent = false;


    /**
     * The update mode to use for update - defaults to the same as the parent operation
     *
     * @var string
     */
    private $updateMode;

    /**
     * UpdatableMappedField constructor.
     *
     * @param string $fieldName
     * The name of the field being mapped to the other datasource
     * @param string $datasourceInstanceKey
     * An array of filters to apply to the parent row if required before applying this mapping.
     * @param array $parentFilters
     * The key of the datasource to map to
     * @param string[] $parentFieldMappings
     * An array of mapped fields to synchronise from the parent dataset to the child datasource.
     * @param array $constantFieldValues
     * An array of fixed values to merge into the child datasource.
     * @param string $updateMode
     * The update mode to use for update - defaults to the same as the parent operation
     * @param string $targetFieldName
     *  This property is used where the input values for the mapping are literal values
     *  rather than objects so we need to create a wrapper object with a single property
     *  named using the target field name.
     */
    public function __construct($fieldName, $datasourceInstanceKey, $parentFilters = [], $parentFieldMappings = [], $constantFieldValues = [], $updateMode = null, $targetFieldName = null, $retainTargetFieldInParent = false) {
        $this->fieldName = $fieldName;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->parentFieldMappings = $parentFieldMappings;
        $this->updateMode = $updateMode;
        $this->targetFieldName = $targetFieldName;
        $this->retainTargetFieldInParent = $retainTargetFieldInParent;
        $this->constantFieldValues = $constantFieldValues;
        $this->parentFilters = $parentFilters;
    }


    /**
     * @return string
     */
    public function getFieldName() {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getDatasourceInstanceKey() {
        return $this->datasourceInstanceKey;
    }

    /**
     * @param string $datasourceInstanceKey
     */
    public function setDatasourceInstanceKey($datasourceInstanceKey) {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
    }

    /**
     * @return array|string
     */
    public function getParentFilters() {
        return $this->parentFilters;
    }

    /**
     * @param array|string $parentFilters
     */
    public function setParentFilters($parentFilters) {
        $this->parentFilters = $parentFilters;
    }

    /**
     * @return string
     */
    public function getParentFieldMappings() {
        return $this->parentFieldMappings;
    }

    /**
     * @param string[] $parentFieldMappings
     */
    public function setParentFieldMappings($parentFieldMappings) {
        $this->parentFieldMappings = $parentFieldMappings;
    }

    /**
     * @return array|string
     */
    public function getConstantFieldValues() {
        return $this->constantFieldValues;
    }

    /**
     * @param array|string $constantFieldValues
     */
    public function setConstantFieldValues($constantFieldValues) {
        $this->constantFieldValues = $constantFieldValues;
    }


    /**
     * @return string
     */
    public function getTargetFieldName() {
        return $this->targetFieldName;
    }

    /**
     * @param string $targetFieldName
     */
    public function setTargetFieldName($targetFieldName) {
        $this->targetFieldName = $targetFieldName;
    }

    /**
     * @return bool
     */
    public function isRetainTargetFieldInParent(): bool {
        return $this->retainTargetFieldInParent;
    }

    /**
     * @param bool $retainTargetFieldInParent
     */
    public function setRetainTargetFieldInParent(bool $retainTargetFieldInParent): void {
        $this->retainTargetFieldInParent = $retainTargetFieldInParent;
    }

    /**
     * @return string
     */
    public function getUpdateMode() {
        return $this->updateMode;
    }

    /**
     * @param string $updateMode
     */
    public function setUpdateMode($updateMode) {
        $this->updateMode = $updateMode;
    }


}
