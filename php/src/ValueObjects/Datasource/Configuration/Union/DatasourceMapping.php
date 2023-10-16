<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\Union;


class DatasourceMapping {

    /**
     * The key for the target datasource
     *
     * @var string
     * @required
     */
    private $key;


    /**
     * @var array
     */
    private $columns;

    /**
     * TargetDatasource constructor.
     * @param string $key
     * @param array $columns
     */
    public function __construct($key, $columns = null) {
        $this->key = $key;
        $this->columns = $columns;
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
     * @return array
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns($columns) {
        $this->columns = $columns;
    }


}