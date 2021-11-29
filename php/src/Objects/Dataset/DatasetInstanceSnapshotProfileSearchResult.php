<?php


namespace Kinintel\Objects\Dataset;


use Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetSnapshotProcessor;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;

class DatasetInstanceSnapshotProfileSearchResult {

    /**
     * The id of the snapshot profile
     *
     * @var integer
     */
    private $id;

    /**
     * The datasource instance key for the snapshot
     *
     * @var string
     */
    private $snapshotProfileDatasourceInstanceKey;

    /**
     * The dataset instance id for which this snapshot profile belongs
     *
     * @var integer
     */
    private $parentDatasetInstanceId;


    /**
     * The parent dataset title
     *
     * @var string
     */
    private $parentDatasetTitle;


    /**
     * The snapshot profile title
     *
     * @var string
     */
    private $snapshotProfileTitle;


    /**
     * The status of the task
     *
     * @var string
     */
    private $taskStatus;

    /**
     * The last start time for the task
     *
     * @var string
     */
    private $taskLastStartTime;


    /**
     * The next start time for the task
     *
     * @var string
     */
    private $taskNextStartTime;


    /**
     * DatasetInstanceSnapshotProfileSearchResult constructor.
     *
     * @param DatasetInstanceSnapshotProfile $snapshotProfile
     */
    public function __construct($snapshotProfile) {

        // Grab snapshot properties
        $this->id = $snapshotProfile->getId();
        $this->snapshotProfileTitle = $snapshotProfile->getTitle();

        $this->snapshotProfileDatasourceInstanceKey = $snapshotProfile->getDataProcessorInstance()->getKey();

        $this->taskStatus = $snapshotProfile->getScheduledTask()->getStatus();
        $this->taskLastStartTime = $snapshotProfile->getScheduledTask()->getLastStartTime();
        $this->taskNextStartTime = $snapshotProfile->getScheduledTask()->getNextStartTime();

        // Grab parent properties
        $this->parentDatasetInstanceId = $snapshotProfile->getDatasetInstanceId();
        $this->parentDatasetTitle = $snapshotProfile->getDatasetInstanceLabel() ? $snapshotProfile->getDatasetInstanceLabel()->getTitle() : "";

    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSnapshotProfileDatasourceInstanceKey() {
        return $this->snapshotProfileDatasourceInstanceKey;
    }

    /**
     * @param string $snapshotProfileDatasourceInstanceKey
     */
    public function setSnapshotProfileDatasourceInstanceKey($snapshotProfileDatasourceInstanceKey) {
        $this->snapshotProfileDatasourceInstanceKey = $snapshotProfileDatasourceInstanceKey;
    }

    /**
     * @return int
     */
    public function getParentDatasetInstanceId() {
        return $this->parentDatasetInstanceId;
    }

    /**
     * @param int $parentDatasetInstanceId
     */
    public function setParentDatasetInstanceId($parentDatasetInstanceId) {
        $this->parentDatasetInstanceId = $parentDatasetInstanceId;
    }

    /**
     * @return string
     */
    public function getParentDatasetTitle() {
        return $this->parentDatasetTitle;
    }

    /**
     * @param string $parentDatasetTitle
     */
    public function setParentDatasetTitle($parentDatasetTitle) {
        $this->parentDatasetTitle = $parentDatasetTitle;
    }

    /**
     * @return string
     */
    public function getSnapshotProfileTitle() {
        return $this->snapshotProfileTitle;
    }

    /**
     * @param string $snapshotProfileTitle
     */
    public function setSnapshotProfileTitle($snapshotProfileTitle) {
        $this->snapshotProfileTitle = $snapshotProfileTitle;
    }


    /**
     * Get full title
     */
    public function getFullTitle() {
        return $this->parentDatasetTitle . ": " . $this->snapshotProfileTitle;
    }

    /**
     * @return string
     */
    public function getTaskStatus() {
        return $this->taskStatus;
    }

    /**
     * @param string $taskStatus
     */
    public function setTaskStatus($taskStatus) {
        $this->taskStatus = $taskStatus;
    }

    /**
     * @return string
     */
    public function getTaskLastStartTime() {
        return $this->taskLastStartTime;
    }

    /**
     * @param string $taskLastStartTime
     */
    public function setTaskLastStartTime($taskLastStartTime) {
        $this->taskLastStartTime = $taskLastStartTime;
    }

    /**
     * @return string
     */
    public function getTaskNextStartTime() {
        return $this->taskNextStartTime;
    }

    /**
     * @param string $taskNextStartTime
     */
    public function setTaskNextStartTime($taskNextStartTime) {
        $this->taskNextStartTime = $taskNextStartTime;
    }


}