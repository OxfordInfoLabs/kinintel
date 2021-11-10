<?php


namespace Kinintel\ValueObjects\Datasource;

/**
 * Base update config class - encodes the core behaviour we
 * require - namely a set of key fields.
 *
 * Class DatasourceUpdateConfig
 * @package Kinintel\ValueObjects\Datasource
 */
class DatasourceUpdateConfig {

    /**
     * Array of key field names used when
     * updating with mode of "update"
     *
     * @var string[]
     */
    private $keyFieldNames;


    /**
     * @var UpdatableMappedField[]
     */
    private $mappedFields = [];

    /**
     * DatasourceUpdateConfig constructor.
     *
     * @param string[] $keyFieldNames
     * @param UpdatableMappedField[] $mappedFields
     */
    public function __construct($keyFieldNames = [], $mappedFields = []) {
        $this->keyFieldNames = $keyFieldNames;
        $this->mappedFields = $mappedFields;
    }


    /**
     * @return string[]
     */
    public function getKeyFieldNames() {
        return $this->keyFieldNames;
    }

    /**
     * @param string[] $keyFieldNames
     */
    public function setKeyFieldNames($keyFieldNames) {
        $this->keyFieldNames = $keyFieldNames;
    }

    /**
     * @return UpdatableMappedField[]
     */
    public function getMappedFields() {
        return $this->mappedFields;
    }

    /**
     * @param UpdatableMappedField[] $mappedFields
     */
    public function setMappedFields($mappedFields) {
        $this->mappedFields = $mappedFields;
    }


}