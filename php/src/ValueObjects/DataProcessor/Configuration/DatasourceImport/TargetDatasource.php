<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;


use Kinintel\Objects\Datasource\UpdatableDatasource;

class TargetDatasource {

    /**
     * The key for the target datasource
     *
     * @var string
     * @required
     */
    private $key;


    /**
     * @var TargetField[]
     */
    private $fields;


    /**
     * @var string
     */
    private $updateMode = UpdatableDatasource::UPDATE_MODE_REPLACE;

    /**
     * TargetDatasource constructor.
     * @param string $key
     * @param TargetField[] $fields
     */
    public function __construct($key, $fields = null, $updateMode = UpdatableDatasource::UPDATE_MODE_REPLACE) {
        $this->key = $key;
        $this->fields = $fields;
        $this->updateMode = $updateMode;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @return TargetField[]
     */
    public function getFields() {
        return $this->fields;
    }

    /**
     * @param TargetField[] $fields
     */
    public function setFields($fields) {
        $this->fields = $fields;
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