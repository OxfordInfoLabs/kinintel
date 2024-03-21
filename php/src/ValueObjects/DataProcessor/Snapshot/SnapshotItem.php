<?php

namespace Kinintel\ValueObjects\DataProcessor\Snapshot;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;

class SnapshotItem {

    /**
     * @var string
     */
    private $key;


    /**
     * @var string
     */
    private $title;


    /**
     * @var string
     */
    private $type;

    /**
     * Trigger (adhoc or scheduled)
     *
     * @var string
     */
    private $trigger;


    /**
     * @var string
     */
    private $datasetTitle;

    /**
     * Matching dataset management key
     *
     * @var string
     */
    private $datasetManagementKey;


    /**
     * Running status
     *
     * @var string
     */
    private $status;


    /**
     * @var string
     */
    private $lastRunTime;


    /**
     * @var string
     */
    private $nextRunTime;


    /**
     * @var mixed
     */
    private $config;

    /**
     * Standard and incremental snapshot
     */
    const STANDARD_SNAPSHOT = "STANDARD";
    const INCREMENTAL_SNAPSHOT = "INCREMENTAL";


    /**
     * Construct a new snapshot item
     *
     * @param string $key
     * @param string $title
     * @param string $type
     * @param mixed $config
     * @param string $trigger
     * @param string $datasetTitle
     * @param string $datasetManagementKey
     * @param string $status
     * @param string $lastRunTime
     * @param string $nextRunTime
     */
    public function __construct($key, $title, $type, $config, $trigger, $datasetTitle, $datasetManagementKey, $status, $lastRunTime, $nextRunTime) {
        $this->key = $key;
        $this->title = $title;
        $this->type = $type;
        $this->trigger = $trigger;
        $this->datasetTitle = $datasetTitle;
        $this->datasetManagementKey = $datasetManagementKey;
        $this->status = $status;
        $this->lastRunTime = $lastRunTime;
        $this->nextRunTime = $nextRunTime;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }


    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getTrigger() {
        return $this->trigger;
    }

    /**
     * @return string
     */
    public function getDatasetManagementKey() {
        return $this->datasetManagementKey;
    }

    /**
     * @return string
     */
    public function getDatasetTitle() {
        return $this->datasetTitle;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getLastRunTime() {
        return $this->lastRunTime;
    }

    /**
     * @return string
     */
    public function getNextRunTime() {
        return $this->nextRunTime;
    }

    /**
     * @param DataProcessorInstance $dataProcessorInstance
     * @param DatasetInstanceSummary $datasetInstance
     *
     * @return SnapshotItem
     */
    public static function fromDataProcessorAndDatasetInstances($dataProcessorInstance, $datasetInstance) {

        return new SnapshotItem($dataProcessorInstance->getKey(), $dataProcessorInstance->getTitle(), $dataProcessorInstance->getType() == "tabulardatasetsnapshot" ? self::STANDARD_SNAPSHOT : self::INCREMENTAL_SNAPSHOT, $dataProcessorInstance->getConfig(),
            $dataProcessorInstance->getTrigger(), $datasetInstance->getTitle(),
            $datasetInstance->getManagementKey(),
            $dataProcessorInstance->getScheduledTask()?->getStatus() ?: ScheduledTask::STATUS_PENDING,
            $dataProcessorInstance->getScheduledTask()?->getLastEndTime()?->format("d/m/Y H:i:s"), $dataProcessorInstance->getScheduledTask()?->getNextStartTime()?->format("d/m/Y H:i:s")
        );
    }


}