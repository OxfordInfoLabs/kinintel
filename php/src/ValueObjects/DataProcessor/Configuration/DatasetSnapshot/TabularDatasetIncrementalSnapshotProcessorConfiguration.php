<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot;

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
     * The name of the field used for detecting newer values in the data set.
     *
     * @var string
     */
    private $newerValuesFieldName;


    /**
     * Which rule is in use for determining late
     *
     * @var string
     */
    private $newerValuesRule = self::LATEST_VALUE_GREATER;

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
     */
    public function __construct($datasetInstanceId, $snapshotIdentifier, $newerValuesFieldName, $newerValuesRule = self::LATEST_VALUE_GREATER, $keyFieldNames = []) {
        $this->datasetInstanceId = $datasetInstanceId;
        $this->snapshotIdentifier = $snapshotIdentifier;
        $this->newerValuesFieldName = $newerValuesFieldName;
        $this->newerValuesRule = $newerValuesRule;
        $this->keyFieldNames = $keyFieldNames;
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


}