<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot;

use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use phpDocumentor\Reflection\Types\Integer;

/**
 * Configuration for the snapshot processor
 *
 * Class TabularDatasetSnapshotProcessorConfiguration
 * @package Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot
 */
class TabularDatasetSnapshotProcessorConfiguration {

    /**
     * The dataset instance for which this snapshot is being made.
     *
     * @var integer
     * @required
     */
    private $datasetInstanceId;


    /**
     * Parameter Values for the data set instance if required
     *
     * @var mixed[]
     */
    private $parameterValues;


    /**
     * @var string
     */
    private $snapshotIdentifier;

    /**
     * @var bool
     */
    private $createHistory;

    /**
     * @var bool
     */
    private $createLatest;

    /**
     * Key field names - used for indexing target snapshot table
     *
     * @var string[]
     */
    private $keyFieldNames;

    /**
     * @var Index[]
     */
    private $indexes;

    /**
     * @var TimeLapseFieldSet[]
     */
    private $timeLapsedFields;

    /**
     * @var int
     */
    private $readChunkSize = null;

    /**
     * TabularDatasetSnapshotProcessorConfiguration constructor.
     * @param string[] $keyFieldNames
     * @param TimeLapseFieldSet[] $timeLapsedFields
     * @param int $datasetInstanceId
     * @param mixed[] $parameterValues
     * @param string $snapshotIdentifier
     * @param bool $createLatest
     * @param bool $createHistory
     * @param int $readChunkSize
     * @param Index[] $indexes
     */
    public function __construct($keyFieldNames = [], $timeLapsedFields = [], $datasetInstanceId = null, $parameterValues = [], $snapshotIdentifier = null, $createLatest = true, $createHistory = true, $readChunkSize = null, $indexes = []) {
        $this->keyFieldNames = $keyFieldNames;
        $this->timeLapsedFields = $timeLapsedFields;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->snapshotIdentifier = $snapshotIdentifier;
        $this->createLatest = $createLatest;
        $this->createHistory = $createHistory;
        $this->readChunkSize = $readChunkSize;
        $this->indexes = $indexes;
        $this->parameterValues = $parameterValues;
    }

    /**
     * @return integer
     */
    public function getDatasetInstanceId() {
        return $this->datasetInstanceId;
    }

    /**
     * @param integer $datasetInstanceId
     */
    public function setDatasetInstanceId($datasetInstanceId) {
        $this->datasetInstanceId = $datasetInstanceId;
    }

    /**
     * @return mixed[]
     */
    public function getParameterValues() {
        return $this->parameterValues;
    }

    /**
     * @param mixed[] $parameterValues
     */
    public function setParameterValues($parameterValues) {
        $this->parameterValues = $parameterValues;
    }




    /**
     * @return string
     */
    public function getSnapshotIdentifier() {
        return $this->snapshotIdentifier;
    }

    /**
     * @param string $snapshotIdentifier
     */
    public function setSnapshotIdentifier($snapshotIdentifier) {
        $this->snapshotIdentifier = $snapshotIdentifier;
    }


    /**
     * @return string[]
     */
    public function getKeyFieldNames() {
        return $this->keyFieldNames;
    }

    /**
     * @param string[] $keyFieldNames
     */
    public function setKeyFieldNames($keyFieldNames) {
        $this->keyFieldNames = $keyFieldNames;
    }

    /**
     * @return Index[]
     */
    public function getIndexes() {
        return $this->indexes;
    }

    /**
     * @param Index[] $indexes
     */
    public function setIndexes($indexes) {
        $this->indexes = $indexes;
    }


    /**
     * @return TimeLapseFieldSet[]
     */
    public function getTimeLapsedFields() {
        return $this->timeLapsedFields;
    }

    /**
     * @param TimeLapseFieldSet[] $timeLapsedFields
     */
    public function setTimeLapsedFields($timeLapsedFields) {
        $this->timeLapsedFields = $timeLapsedFields;
    }

    /**
     * @return bool
     */
    public function isCreateHistory() {
        return $this->createHistory;
    }

    /**
     * @param bool $createHistory
     */
    public function setCreateHistory($createHistory) {
        $this->createHistory = $createHistory;
    }

    /**
     * @return bool
     */
    public function isCreateLatest() {
        return $this->createLatest;
    }

    /**
     * @param bool $createLatest
     */
    public function setCreateLatest($createLatest) {
        $this->createLatest = $createLatest;
    }

    /**
     * @return int
     */
    public function getReadChunkSize() {
        return $this->readChunkSize;
    }

    /**
     * @param int $readChunkSize
     */
    public function setReadChunkSize($readChunkSize) {
        $this->readChunkSize = $readChunkSize;
    }


}