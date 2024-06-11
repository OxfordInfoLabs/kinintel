<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot;

use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorAction;
use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorActions;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;

/**
 * Configuration for the snapshot processor
 *
 * Class TabularDatasetSnapshotProcessorConfiguration
 * @package Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot
 */
class TabularDatasetSnapshotProcessorConfiguration {

    use DataProcessorActions;

    /**
     * Parameter Values for the data set instance if required
     *
     * @var mixed[]
     */
    private $parameterValues;

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
     * @param mixed[] $parameterValues
     * @param bool $createLatest
     * @param bool $createHistory
     * @param int $readChunkSize
     * @param Index[] $indexes
     */
    public function __construct($keyFieldNames = [], $timeLapsedFields = [], $parameterValues = [], $createLatest = true, $createHistory = true, $readChunkSize = null, $indexes = []) {
        $this->keyFieldNames = $keyFieldNames;
        $this->timeLapsedFields = $timeLapsedFields;
        $this->createLatest = $createLatest;
        $this->createHistory = $createHistory;
        $this->readChunkSize = $readChunkSize;
        $this->indexes = $indexes;
        $this->parameterValues = $parameterValues;
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


    public function getProcessorActions($dataProcessorInstanceKey) {
       $actions = [];
       if ($this->createLatest){
           $actions[] = new DataProcessorAction("Latest", $dataProcessorInstanceKey."_latest");
       }
       if ($this->createHistory){
           $actions[] = new DataProcessorAction("Historical Entries", $dataProcessorInstanceKey);
       }

       return $actions;
    }
}