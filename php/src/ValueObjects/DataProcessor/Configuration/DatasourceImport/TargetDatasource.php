<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;



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
     * TargetDatasource constructor.
     * @param string $key
     * @param Field[] $fields
     */
    public function __construct($key, $fields = null) {
        $this->key = $key;
        $this->fields = $fields;
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


}