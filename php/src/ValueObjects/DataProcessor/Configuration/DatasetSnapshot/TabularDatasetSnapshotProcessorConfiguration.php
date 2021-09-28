<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot;

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
     * @var string
     */
    private $snapshotIdentifier;


    /**
     * Key field names - used for indexing target snapshot table
     *
     * @var string[]
     */
    private $keyFieldNames;

    /**
     * @var TimeLapseFieldSet[]
     */
    private $timeLapsedFields;

    /**
     * TabularDatasetSnapshotProcessorConfiguration constructor.
     * @param string[] $keyFieldNames
     * @param TimeLapseFieldSet[] $timeLapsedFields
     */
    public function __construct($keyFieldNames = [], $timeLapsedFields = [], $datasetInstanceId = null, $snapshotIdentifier = null) {
        $this->keyFieldNames = $keyFieldNames;
        $this->timeLapsedFields = $timeLapsedFields;
        $this->datasetInstanceId = $datasetInstanceId;
        $this->snapshotIdentifier = $snapshotIdentifier;
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


}