<?php


namespace Kinintel\Objects\Dataset;


use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;

class DatasetInstanceSnapshotProfileSummary {


    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var ScheduledTaskTimePeriod[]
     */
    private $taskTimePeriods;


    /**
     * @var string
     */
    private $taskStatus;

    /**
     * @var string
     */
    private $taskLastStartTime;

    /**
     * @var string
     */
    private $taskLastEndTime;


    /**
     * @var string
     */
    private $taskNextStartTime;


    /**
     * @var string
     */
    private $processorType;

    /**
     * @var mixed
     */
    private $processorConfig;

    /**
     * DatasetInstanceSnapshotProfileSummary constructor.
     *
     * @param string $title
     * @param ScheduledTaskTimePeriod[] $taskTimePeriods
     * @param string $processorType
     * @param mixed $processorConfig
     * @param string $taskStatus
     * @param string $taskLastStartTime
     * @param string $taskLastEndTime
     * @param string $taskNextStartTime
     */
    public function __construct($title, $taskTimePeriods, $processorType, $processorConfig, $taskStatus = null, $taskLastStartTime = null,
                                $taskLastEndTime = null, $taskNextStartTime = null, $id = null) {
        $this->title = $title;
        $this->taskTimePeriods = $taskTimePeriods;
        $this->processorType = $processorType;
        $this->processorConfig = $processorConfig;
        $this->taskStatus = $taskStatus;
        $this->taskLastStartTime = $taskLastStartTime instanceof \DateTime ? $taskLastStartTime->format("d/m/Y H:i:s") : "";
        $this->taskLastEndTime = $taskLastEndTime instanceof \DateTime ? $taskLastEndTime->format("d/m/Y H:i:s") : "";
        $this->taskNextStartTime = $taskNextStartTime instanceof \DateTime ? $taskNextStartTime->format("d/m/Y H:i:s") : "";
        $this->id = $id;
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
     * @return ScheduledTaskTimePeriod[]
     */
    public function getTaskTimePeriods() {
        return $this->taskTimePeriods;
    }

    /**
     * @param ScheduledTaskTimePeriod[] $taskTimePeriods
     */
    public function setTaskTimePeriods($taskTimePeriods) {
        $this->taskTimePeriods = $taskTimePeriods;
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
    public function getTaskLastEndTime() {
        return $this->taskLastEndTime;
    }

    /**
     * @param string $taskLastEndTime
     */
    public function setTaskLastEndTime($taskLastEndTime) {
        $this->taskLastEndTime = $taskLastEndTime;
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

    /**
     * @return string
     */
    public function getProcessorType() {
        return $this->processorType;
    }

    /**
     * @param string $processorType
     */
    public function setProcessorType($processorType) {
        $this->processorType = $processorType;
    }


    /**
     * @return mixed
     */
    public function getProcessorConfig() {
        return $this->processorConfig;
    }

    /**
     * @param mixed $processorConfig
     */
    public function setProcessorConfig($processorConfig) {
        $this->processorConfig = $processorConfig;
    }


}
