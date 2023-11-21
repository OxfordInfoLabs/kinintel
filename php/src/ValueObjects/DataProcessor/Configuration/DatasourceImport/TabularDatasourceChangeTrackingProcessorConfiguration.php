<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;

use Kinintel\Objects\Dataset\DatasetInstance;

class TabularDatasourceChangeTrackingProcessorConfiguration {

    /**
     * @var string[]
     * @requiredEither sourceDataset,sourceDatasources
     */
    private $sourceDatasourceKeys;

    /**
     * @var DatasetInstance
     */
    private $sourceDataset;

    /**
     * @var SourceDatasource[]
     */
    private $sourceDatasources;

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
     * @var string
     */
    private $offsetField;

    /**
     * @var int
     */
    private $initialOffset;

    /**
     * @param string[] $sourceDatasourceKeys
     * @param SourceDatasource[] $sourceDatasources
     * @param DatasetInstance $sourceDataset
     * @param string $targetLatestDatasourceKey
     * @param string $targetChangeDatasourceKey
     * @param string $targetSummaryDatasourceKey
     * @param string[] $summaryFields
     * @param int $sourceReadChunkSize
     * @param int $targetWriteChunkSize
     * @param string $offsetField
     * @param int $initialOffset
     */
    public function __construct($sourceDatasourceKeys = [], $sourceDatasources = [], $sourceDataset = null, $targetLatestDatasourceKey = null, $targetChangeDatasourceKey = null, $targetSummaryDatasourceKey = null, $summaryFields = [], $sourceReadChunkSize = null, $targetWriteChunkSize = null, $offsetField = null, $initialOffset = 0) {
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
        $this->targetLatestDatasourceKey = $targetLatestDatasourceKey;
        $this->targetChangeDatasourceKey = $targetChangeDatasourceKey;
        $this->targetSummaryDatasourceKey = $targetSummaryDatasourceKey;
        $this->summaryFields = $summaryFields;
        $this->sourceReadChunkSize = $sourceReadChunkSize ?: PHP_INT_MAX;
        $this->targetWriteChunkSize = $targetWriteChunkSize ?: 500;
        $this->sourceDataset = $sourceDataset;
        $this->sourceDatasources = $sourceDatasources;
        $this->offsetField = $offsetField;
        $this->initialOffset = $initialOffset;
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
     * @return SourceDatasource[]
     */
    public function getSourceDatasources() {
        return $this->sourceDatasources;
    }

    /**
     * @param SourceDatasource[] $sourceDatasources
     */
    public function setSourceDatasources($sourceDatasources) {
        $this->sourceDatasources = $sourceDatasources;
    }

    /**
     * @return DatasetInstance
     */
    public function getSourceDataset() {
        return $this->sourceDataset;
    }

    /**
     * @param DatasetInstance $sourceDataset
     */
    public function setSourceDataset($sourceDataset) {
        $this->sourceDataset = $sourceDataset;
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

    /**
     * @return string
     */
    public function getOffsetField() {
        return $this->offsetField;
    }

    /**
     * @param string $offsetField
     */
    public function setOffsetField($offsetField) {
        $this->offsetField = $offsetField;
    }

    /**
     * @return int
     */
    public function getInitialOffset() {
        return $this->initialOffset;
    }

    /**
     * @param int $initialOffset
     */
    public function setInitialOffset($initialOffset) {
        $this->initialOffset = $initialOffset;
    }

}