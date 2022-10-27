<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;

use Kinintel\Objects\Dataset\DatasetInstance;

class TabularDatasourceChangeTrackingProcessorConfiguration {

    /**
     * @var string[]
     */
    private $sourceDatasourceKeys;
    /**
     * @var string
     */
    private $targetLatestDatasourceKey;

    /**
     * @var string
     */
    private $targetChangeDatasourceKey;

    /**
     * @var string
     */
    private $targetSummaryDatasourceKey;

    /**
     * @var string[]
     */
    private $summaryFields;

    /**
     * @var int
     */
    private $sourceReadChunkSize;

    /**
     * @var int
     */
    private $targetWriteChunkSize;

    /**
     * @param string[] $sourceDatasourceKeys
     * @param string $targetLatestDatasourceKey
     * @param string $targetChangeDatasourceKey
     * @param string $targetSummaryDatasourceKey
     * @param string[] $summaryFields
     * @param int $sourceReadChunkSize
     * @param int $targetWriteChunkSize
     */
    public function __construct($sourceDatasourceKeys, $targetLatestDatasourceKey = null, $targetChangeDatasourceKey = null, $targetSummaryDatasourceKey = null, $summaryFields = [], $sourceReadChunkSize = null, $targetWriteChunkSize = null) {
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
        $this->targetLatestDatasourceKey = $targetLatestDatasourceKey;
        $this->targetChangeDatasourceKey = $targetChangeDatasourceKey;
        $this->targetSummaryDatasourceKey = $targetSummaryDatasourceKey;
        $this->summaryFields = $summaryFields;
        $this->sourceReadChunkSize = $sourceReadChunkSize ?: PHP_INT_MAX;
        $this->targetWriteChunkSize = $targetWriteChunkSize ?: 500;
    }

    /**
     * @return string[]
     */
    public function getSourceDatasourceKeys() {
        return $this->sourceDatasourceKeys;
    }

    /**
     * @param string[] $sourceDatasourceKeys
     */
    public function setSourceDatasourceKeys($sourceDatasourceKeys) {
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
    }

    /**
     * @return string
     */
    public function getTargetLatestDatasourceKey() {
        return $this->targetLatestDatasourceKey;
    }

    /**
     * @param string $targetLatestDatasourceKey
     */
    public function setTargetLatestDatasourceKey($targetLatestDatasourceKey) {
        $this->targetLatestDatasourceKey = $targetLatestDatasourceKey;
    }

    /**
     * @return string
     */
    public function getTargetChangeDatasourceKey() {
        return $this->targetChangeDatasourceKey;
    }

    /**
     * @param string $targetChangeDatasourceKey
     */
    public function setTargetChangeDatasourceKey($targetChangeDatasourceKey) {
        $this->targetChangeDatasourceKey = $targetChangeDatasourceKey;
    }

    /**
     * @return string
     */
    public function getTargetSummaryDatasourceKey() {
        return $this->targetSummaryDatasourceKey;
    }

    /**
     * @param string $targetSummaryDatasourceKey
     */
    public function setTargetSummaryDatasourceKey($targetSummaryDatasourceKey) {
        $this->targetSummaryDatasourceKey = $targetSummaryDatasourceKey;
    }

    /**
     * @return string[]
     */
    public function getSummaryFields() {
        return $this->summaryFields;
    }

    /**
     * @param string[] $summaryFields
     */
    public function setSummaryFields($summaryFields) {
        $this->summaryFields = $summaryFields;
    }

    /**
     * @return int
     */
    public function getSourceReadChunkSize() {
        return $this->sourceReadChunkSize;
    }

    /**
     * @param int $sourceReadChunkSize
     */
    public function setSourceReadChunkSize($sourceReadChunkSize) {
        $this->sourceReadChunkSize = $sourceReadChunkSize;
    }

    /**
     * @return int
     */
    public function getTargetWriteChunkSize() {
        return $this->targetWriteChunkSize;
    }

    /**
     * @param int $targetWriteChunkSize
     */
    public function setTargetWriteChunkSize($targetWriteChunkSize) {
        $this->targetWriteChunkSize = $targetWriteChunkSize;
    }

}