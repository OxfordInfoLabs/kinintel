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
     * @requiredEither targetChangeDatasourceKey,targetAddsDatasourceKey,targetSummaryDatasourceKey
     */
    private $targetLatestDatasourceKey;

    /**
     * @var string
     */
    private $targetChangeDatasourceKey;


    /**
     * A datasource to simply receive new adds (as opposed to all changes)
     *
     * @var string
     */
    private $targetAddsDatasourceKey;


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
     * @var mixed
     */
    private $initialOffset;


    /**
     * @var string
     */
    private $offsetParameterName;

    /**
     * @var int
     */
    private $changeLimit;

    /**
     * @var bool
     */
    private $updatePreviousWhenTooManyChanges;

    /**
     * @param string[] $sourceDatasourceKeys
     * @param SourceDatasource[] $sourceDatasources
     * @param DatasetInstance $sourceDataset
     * @param string $targetLatestDatasourceKey
     * @param string $targetChangeDatasourceKey
     * @param string $targetAddsDatasourceKey
     * @param string $targetSummaryDatasourceKey
     * @param string[] $summaryFields
     * @param int $sourceReadChunkSize
     * @param int $targetWriteChunkSize
     * @param string $offsetField
     * @param mixed $initialOffset
     * @param string $offsetParameterName
     * @param int $changeLimit
     * @param bool $updatePreviousWhenTooManyChanges
     */
    public function __construct($sourceDatasourceKeys = [], $sourceDatasources = [], $sourceDataset = null, $targetLatestDatasourceKey = null, $targetChangeDatasourceKey = null, $targetAddsDatasourceKey = null, $targetSummaryDatasourceKey = null, $summaryFields = [], $sourceReadChunkSize = null, $targetWriteChunkSize = null, $offsetField = null, $initialOffset = 0,
                                $offsetParameterName = null, $changeLimit = null, $updatePreviousWhenTooManyChanges = false) {
        $this->sourceDatasourceKeys = $sourceDatasourceKeys;
        $this->targetLatestDatasourceKey = $targetLatestDatasourceKey;
        $this->targetChangeDatasourceKey = $targetChangeDatasourceKey;
        $this->targetAddsDatasourceKey = $targetAddsDatasourceKey;
        $this->targetSummaryDatasourceKey = $targetSummaryDatasourceKey;
        $this->summaryFields = $summaryFields;
        $this->sourceReadChunkSize = $sourceReadChunkSize ?: PHP_INT_MAX;
        $this->targetWriteChunkSize = $targetWriteChunkSize ?: 500;
        $this->sourceDataset = $sourceDataset;
        $this->sourceDatasources = $sourceDatasources;
        $this->offsetField = $offsetField;
        $this->initialOffset = $initialOffset;
        $this->offsetParameterName = $offsetParameterName;
        $this->changeLimit = $changeLimit;
        $this->updatePreviousWhenTooManyChanges = $updatePreviousWhenTooManyChanges;
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
    public function getTargetAddsDatasourceKey() {
        return $this->targetAddsDatasourceKey;
    }

    /**
     * @param string $targetAddsDatasourceKey
     */
    public function setTargetAddsDatasourceKey($targetAddsDatasourceKey) {
        $this->targetAddsDatasourceKey = $targetAddsDatasourceKey;
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
     * @return mixed
     */
    public function getInitialOffset() {
        return $this->initialOffset;
    }

    /**
     * @param mixed $initialOffset
     */
    public function setInitialOffset($initialOffset) {
        $this->initialOffset = $initialOffset;
    }

    /**
     * @return string|null
     */
    public function getOffsetParameterName(): ?string {
        return $this->offsetParameterName;
    }

    /**
     * @param string|null $offsetParameterName
     */
    public function setOffsetParameterName(?string $offsetParameterName): void {
        $this->offsetParameterName = $offsetParameterName;
    }

    public function getChangeLimit(): ?int {
        return $this->changeLimit;
    }

    public function setChangeLimit(?int $changeLimit): void {
        $this->changeLimit = $changeLimit;
    }

    public function isUpdatePreviousWhenTooManyChanges(): bool {
        return $this->updatePreviousWhenTooManyChanges;
    }

    public function setUpdatePreviousWhenTooManyChanges(bool $updatePreviousWhenTooManyChanges): void {
        $this->updatePreviousWhenTooManyChanges = $updatePreviousWhenTooManyChanges;
    }

}