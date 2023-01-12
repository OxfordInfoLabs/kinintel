<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;

class TabularDatasourceAggregatingSource {

    /**
     * @var string
     */
    private $key;

    /**
     * @var string[string]
     */
    private $columnMappings = [];

    /**
     * @var string
     */
    private $dateColumn;

    /**
     * @var string
     */
    private $sourceIndicatorColumn;

    /**
     * @param string $key
     * @param mixed $columnMappings
     * @param $dateColumn
     * @param string $sourceIndicatorColumn
     */
    public function __construct($key, $columnMappings = [], $dateColumn = null, $sourceIndicatorColumn = null) {
        $this->key = $key;
        $this->columnMappings = $columnMappings;
        $this->dateColumn = $dateColumn;
        $this->sourceIndicatorColumn = $sourceIndicatorColumn;
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
     * @return mixed
     */
    public function getColumnMappings() {
        return $this->columnMappings;
    }

    /**
     * @param mixed $columnMappings
     */
    public function setColumnMappings($columnMappings) {
        $this->columnMappings = $columnMappings;
    }

    /**
     * @return string
     */
    public function getDateColumn() {
        return $this->dateColumn;
    }

    /**
     * @param string $dateColumn
     */
    public function setDateColumn($dateColumn) {
        $this->dateColumn = $dateColumn;
    }

    /**
     * @return string
     */
    public function getSourceIndicatorColumn() {
        return $this->sourceIndicatorColumn;
    }

    /**
     * @param string $sourceIndicatorColumn
     */
    public function setSourceIndicatorColumn($sourceIndicatorColumn) {
        $this->sourceIndicatorColumn = $sourceIndicatorColumn;
    }

}