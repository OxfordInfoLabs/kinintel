<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;

class TabularDatasourceAggregatingProcessorConfiguration {

    /**
     * @var TabularDatasourceAggregatingSource[]
     */
    private $sourceDatasources;

    /**
     * @var string
     */
    private $targetDatasourceKey;

    /**
     * @var string[]
     */
    private $keyFields = [];

    /**
     * @var string
     */
    private $frequency = self::FREQUENCY_DAILY;

    /**
     * @var int
     */
    private $sourceReadChunkSize = 500;

    /**
     * @var int
     */
    private $targetWriteChunkSize = 500;


    const FREQUENCY_HOURLY = "hourly";
    const FREQUENCY_DAILY = "daily";
    const FREQUENCY_WEEKLY = "weekly";

    /**
     * @param TabularDatasourceAggregatingSource[] $sourceDatasources
     * @param string $targetDatasourceKey
     * @param array $keyFields
     * @param string $frequency
     * @param int $sourceReadChunkSize
     * @param int $targetWriteChunkSize
     */
    public function __construct($sourceDatasources, $targetDatasourceKey, $keyFields = [], $frequency = self::FREQUENCY_DAILY, $sourceReadChunkSize = 500, $targetWriteChunkSize = 500) {
        $this->sourceDatasources = $sourceDatasources;
        $this->targetDatasourceKey = $targetDatasourceKey;
        $this->keyFields = $keyFields;
        $this->sourceReadChunkSize = $sourceReadChunkSize;
        $this->targetWriteChunkSize = $targetWriteChunkSize;
        $this->frequency = $frequency;
    }

    /**
     * @return TabularDatasourceAggregatingSource[]
     */
    public function getSourceDatasources() {
        return $this->sourceDatasources;
    }

    /**
     * @param TabularDatasourceAggregatingSource[] $sourceDatasources
     */
    public function setSourceDatasources($sourceDatasources) {
        $this->sourceDatasources = $sourceDatasources;
    }

    /**
     * @return string
     */
    public function getTargetDatasourceKey() {
        return $this->targetDatasourceKey;
    }

    /**
     * @param string $targetDatasourceKey
     */
    public function setTargetDatasourceKey($targetDatasourceKey) {
        $this->targetDatasourceKey = $targetDatasourceKey;
    }

    /**
     * @return string[]
     */
    public function getKeyFields() {
        return $this->keyFields;
    }

    /**
     * @param string[] $keyFields
     */
    public function setKeyFields($keyFields) {
        $this->keyFields = $keyFields;
    }

    /**
     * @return string
     */
    public function getFrequency() {
        return $this->frequency;
    }

    /**
     * @param string $frequency
     */
    public function setFrequency($frequency) {
        $this->frequency = $frequency;
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