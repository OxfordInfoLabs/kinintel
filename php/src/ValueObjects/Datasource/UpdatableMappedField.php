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
     * An array of mapped fields to synchronise from the parent dataset to the child datasource.
     *
     * @var string[string]
     */
    private $parentFieldMappings;


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
     * @param string $datasourceInstanceKey
     * @param string[] $parentFieldMappings
     * @param string $updateMode
     */
    public function __construct($fieldName, $datasourceInstanceKey, $parentFieldMappings = [], $updateMode = null) {
        $this->fieldName = $fieldName;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->parentFieldMappings = $parentFieldMappings;
        $this->updateMode = $updateMode;
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
