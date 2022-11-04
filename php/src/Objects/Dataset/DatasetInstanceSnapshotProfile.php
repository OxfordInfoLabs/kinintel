<?php


namespace Kinintel\Objects\Dataset;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;

/**
 * Class DatasetInstanceSnapshot
 * @package Kinintel\Objects\Dataset
 *
 * @table ki_dataset_instance_snapshot_profile
 * @generate
 */
class DatasetInstanceSnapshotProfile extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;


    /**
     * @var integer
     */
    private $datasetInstanceId;

    /**
     * @var string
     */
    private $title;


    /**
     * @var string
     * @values adhoc,scheduled
     */
    private $trigger;

    /**
     * @var ScheduledTask
     * @manyToOne
     * @parentJoinColumns scheduled_task_id
     * @saveCascade
     */
    private $scheduledTask;


    /**
     * @var DataProcessorInstance
     * @manyToOne
     * @parentJoinColumns dataprocessor_instance_id
     * @saveCascade
     */
    private $dataProcessorInstance;


    /**
     * @var DatasetInstanceLabel
     * @manyToOne
     * @readOnly
     * @parentJoinColumns dataset_instance_id
     */
    private $datasetInstanceLabel;


    /**
     * DatasetInstanceSnapshotProfile constructor.
     * @param integer $datasetInstanceId
     * @param string $title
     * @param string $trigger
     * @param ScheduledTask $scheduledTask
     * @param DataProcessorInstance $dataProcessorInstance
     */
    public function __construct($datasetInstanceId, $title, $trigger = DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, $scheduledTask, $dataProcessorInstance) {
        $this->datasetInstanceId = $datasetInstanceId;
        $this->title = $title;
        $this->scheduledTask = $scheduledTask;
        $this->dataProcessorInstance = $dataProcessorInstance;
        $this->trigger = $trigger;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
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
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTrigger() {
        return $this->trigger;
    }

    /**
     * @param string $trigger
     */
    public function setTrigger($trigger) {
        $this->trigger = $trigger;
    }

    /**
     * @return ScheduledTask
     */
    public function getScheduledTask() {
        return $this->scheduledTask;
    }

    /**
     * @param ScheduledTask $scheduledTask
     */
    public function setScheduledTask($scheduledTask) {
        $this->scheduledTask = $scheduledTask;
    }

    /**
     * @return DataProcessorInstance
     */
    public function getDataProcessorInstance() {
        return $this->dataProcessorInstance;
    }

    /**
     * @param DataProcessorInstance $dataProcessorInstance
     */
    public function setDataProcessorInstance($dataProcessorInstance) {
        $this->dataProcessorInstance = $dataProcessorInstance;
    }

    /**
     * @return DatasetInstanceLabel
     */
    public function getDatasetInstanceLabel() {
        return $this->datasetInstanceLabel;
    }


    /**
     * Return a summary object
     */
    public function returnSummary() {

        $scheduledTask = $this->scheduledTask ?? new ScheduledTask(null);
        $dataProcessor = $this->dataProcessorInstance ?? new DataProcessorInstance(null, null, null);

        return new DatasetInstanceSnapshotProfileSummary($this->title,
            $dataProcessor->getType(), $dataProcessor->getConfig(), $this->trigger, $scheduledTask->getTimePeriods(), $scheduledTask->getStatus(), $scheduledTask->getLastStartTime(),
            $scheduledTask->getLastEndTime(), $scheduledTask->getNextStartTime(), $this->id
        );
    }


}