<?php

namespace Kinintel\ValueObjects\Util\Analysis\StatisticalAnalysis\Distance;

use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\DistanceAndClusteringProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Configuration\Analysis\StatisticalAnalysis\KMeansClusterConfiguration;

class DistanceConfig {
    /**
     * @var int
     * @requiredEither datasourceKey
     */
    protected $datasetId;

    /**
     * @var string
     *
     */
    protected $datasourceKey;

    /**
     * @var string
     * @required
     */
    protected $keyFieldName;

    /**
     * @var string
     * @required
     */
    protected $componentFieldName;

    /**
     * @var string
     * @required
     */
    protected $valueFieldName;

    /**
     * Construct processor
     *
     * @param string $datasourceKey
     * @param string $datasetId
     * @param string $keyFieldName
     * @param string $componentFieldName
     * @param string $valueFieldName
     */
    public function __construct($datasourceKey, $datasetId, $keyFieldName, $componentFieldName, $valueFieldName) {
        $this->datasourceKey = $datasourceKey;
        $this->datasetId = $datasetId;
        $this->keyFieldName = $keyFieldName;
        $this->componentFieldName = $componentFieldName;
        $this->valueFieldName = $valueFieldName;
    }

    /**
     * @return string
     */
    public function getKeyFieldName() {
        return $this->keyFieldName;
    }

    /**
     * @return string
     */
    public function getComponentFieldName() {
        return $this->componentFieldName;
    }

    /**
     * @param string $keyFieldName
     */
    public function setKeyFieldName($keyFieldName) {
        $this->keyFieldName = $keyFieldName;
    }

    /**
     * @param string $componentFieldName
     */
    public function setComponentFieldName($componentFieldName) {
        $this->componentFieldName = $componentFieldName;
    }

    /**
     * @param int $datasetId
     */
    public function setDatasetId($datasetId) {
        $this->datasetId = $datasetId;
    }

    /**
     * @return string
     */
    public function getValueFieldName() {
        return $this->valueFieldName;
    }

    /**
     * @param string $datasourceKey
     */
    public function setDatasourceKey($datasourceKey) {
        $this->datasourceKey = $datasourceKey;
    }

    /**
     * @return int
     */
    public function getDatasetId() {
        return $this->datasetId;
    }

    /**
     * @return string
     */
    public function getDatasourceKey() {
        return $this->datasourceKey;
    }

    /**
     * @param string $valueFieldName
     */
    public function setValueFieldName($valueFieldName) {
        $this->valueFieldName = $valueFieldName;
    }
}