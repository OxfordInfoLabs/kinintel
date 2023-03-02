<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;

class SourceDatasource {

    /**
     * @var string
     */
    private $datasourceKey;

    /**
     * @var array
     */
    private $parameterSets;

    /**
     * @param string $datasourceKey
     * @param array $parameterSets
     */
    public function __construct($datasourceKey, $parameterSets = []) {
        $this->datasourceKey = $datasourceKey;
        $this->parameterSets = $parameterSets;
    }

    /**
     * @return string
     */
    public function getDatasourceKey() {
        return $this->datasourceKey;
    }

    /**
     * @param string $datasourceKey
     */
    public function setDatasourceKey($datasourceKey) {
        $this->datasourceKey = $datasourceKey;
    }

    /**
     * @return array
     */
    public function getParameterSets() {
        return $this->parameterSets;
    }

    /**
     * @param array $parameterSets
     */
    public function setParameterSets($parameterSets) {
        $this->parameterSets = $parameterSets;
    }

}