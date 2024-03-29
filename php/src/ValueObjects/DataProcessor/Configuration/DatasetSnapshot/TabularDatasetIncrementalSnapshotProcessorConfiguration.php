<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot;

use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;

class TabularDatasetIncrementalSnapshotProcessorConfiguration {

    /**
     * @var integer
     * @required
     */
    private $datasetInstanceId;

    /**
     * @var string
     */
    private $snapshotIdentifier;

    /**
     * Key fields which form the primary key for the data set.
     * If not supplied it is assumed that all values form the primary key
     *
     * @var string[]
     */
    private $keyFieldNames = [];


    /**
     * @var Index[]
     */
    private $indexes = [];


    /**
     * The name of the field used for detecting newer values in the data set.
     *
     * @var string
     */
    private $newerValuesFieldName;


    /**
     * Which rule is in use for determining latest items
     *
     * @var string
     */
    private $newerValuesRule = self::LATEST_VALUE_GREATER;


    /**
     * @var int
     */
    private $readChunkSize = null;


    const LATEST_VALUE_GREATER = "GREATER";
    const LATEST_VALUE_GREATER_OR_EQUAL = "GREATER_OR_EQUAL";
    const LATEST_VALUE_LESSER = "LESSER";
    const LATEST_VALUE_LESSER_OR_EQUAL = "LESSER_OR_EQUAL";


    /**
     * Construct
     *
     * @param integer $datasetInstanceId
     * @param string $snapshotIdentifier
     * @param string $newerValuesFieldName
     * @param string $newerValuesRule
     * @param string[] $keyFieldNames
     * @param array $indexes
     * @param int $readChunkSize
     */
    public function __construct($datasetInstanceId, $snapshotIdentifier, $newerValuesFieldName, $newerValuesRule = self::LATEST_VALUE_GREATER, $keyFieldNames = [], $indexes = [], $readChunkSize = null) {
        $this->datasetInstanceId = $datasetInstanceId;
        $this->snapshotIdentifier = $snapshotIdentifier;
        $this->newerValuesFieldName = $newerValuesFieldName;
        $this->newerValuesRule = $newerValuesRule;
        $this->keyFieldNames = $keyFieldNames;
        $this->readChunkSize = $readChunkSize;
        $this->indexes = $indexes;
    }


    /**
     * @return int
     */
    public function getDatasetInstanceId() {
        return $this->datasetInstanceId;
    }

    /**
     * @param int $datasetInstanceId
     */
    public function setDatasetInstanceId($datasetInstanceId) {
        $this->datasetInstanceId = $datasetInstanceId;
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
     * @return string
     */
    public function getNewerValuesFieldName() {
        return $this->newerValuesFieldName;
    }

    /**
     * @param string $newerValuesFieldName
     */
    public function setNewerValuesFieldName($newerValuesFieldName) {
        $this->newerValuesFieldName = $newerValuesFieldName;
    }

    /**
     * @return string
     */
    public function getNewerValuesRule() {
        return $this->newerValuesRule;
    }

    /**
     * @param string $newerValuesRule
     */
    public function setNewerValuesRule($newerValuesRule) {
        $this->newerValuesRule = $newerValuesRule;
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