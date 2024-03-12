<?php

namespace Kinintel\ValueObjects\DataProcessor;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;

class DataProcessorItem {

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
     * @var mixed
     */
    private $config;


    /**
     * @var string
     */
    private $trigger;


    /**
     * Related object type if relevant
     *
     * @var string
     */
    private $relatedObjectType;


    /**
     * Primary key for related object if relevant
     *
     * @var string
     */
    private $relatedObjectPrimaryKey;


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
     * @param string $title
     * @param string $type
     * @param mixed $config
     * @param string $trigger
     * @param string $relatedObjectType
     * @param mixed $relatedObjectPrimaryKey
     * @param ScheduledTaskTimePeriod[] $taskTimePeriods
     * @param string $taskStatus
     * @param string $taskLastStartTime
     * @param string $taskLastEndTime
     * @param string $taskNextStartTime
     * @param string $key
     */
    public function __construct($title, $type, $config, $trigger = DataProcessorInstance::TRIGGER_ADHOC, $relatedObjectType = null, $relatedObjectPrimaryKey = null, $taskTimePeriods = [], $taskStatus = null, $taskLastStartTime = null, $taskLastEndTime = null, $taskNextStartTime = null, $key = null) {
        $this->key = $key;
        $this->title = $title;
        $this->type = $type;
        $this->config = $config;
        $this->trigger = $trigger;
        $this->taskTimePeriods = $taskTimePeriods;
        $this->taskStatus = $taskStatus;
        $this->taskLastStartTime = $taskLastStartTime;
        $this->taskLastEndTime = $taskLastEndTime;
        $this->taskNextStartTime = $taskNextStartTime;
        $this->relatedObjectType = $relatedObjectType;
        $this->relatedObjectPrimaryKey = $relatedObjectPrimaryKey;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
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
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config) {
        $this->config = $config;
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
     * @return string|null
     */
    public function getRelatedObjectType() {
        return $this->relatedObjectType;
    }

    /**
     * @param string|null $relatedObjectType
     */
    public function setRelatedObjectType($relatedObjectType) {
        $this->relatedObjectType = $relatedObjectType;
    }

    /**
     * @return mixed|string
     */
    public function getRelatedObjectPrimaryKey() {
        return $this->relatedObjectPrimaryKey;
    }

    /**
     * @param mixed|string $relatedObjectPrimaryKey
     */
    public function setRelatedObjectPrimaryKey($relatedObjectPrimaryKey) {
        $this->relatedObjectPrimaryKey = $relatedObjectPrimaryKey;
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
     * @param $projectKey
     * @param $accountId
     * @return void
     */
    public function toDataProcessorInstance($projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Handle new and existing cases
        $key = $this->getKey() ?: $this->getType() . "_" . ($accountId ?? 0) . "_" . date("U");

        $scheduledTask = new ScheduledTask(
            new ScheduledTaskSummary("dataprocessor", $key, ["dataProcessorKey" => $key], []), $projectKey, $accountId);

        // Update the scheduled task
        if ($this->getTrigger() == DataProcessorInstance::TRIGGER_SCHEDULED) {
            $scheduledTask->setTimePeriods($this->getTaskTimePeriods() ?? []);
        } else {
            $scheduledTask->setTimePeriods([]);
            $scheduledTask->setNextStartTime(null);
        }

        // Create a processor
        return new DataProcessorInstance($key, $this->getTitle(),
            $this->getType(), $this->getConfig(),
            $this->getTrigger(), $scheduledTask, $this->getRelatedObjectType(), $this->getRelatedObjectPrimaryKey(), $projectKey, $accountId);


    }

    /**
     * @param DataProcessorInstance $dataProcessorInstance
     * @return DataProcessorItem
     */
    public static function fromDataProcessorInstance($dataProcessorInstance) {
        return new DataProcessorItem($dataProcessorInstance->getTitle(), $dataProcessorInstance->getType(), $dataProcessorInstance->getConfig(),
            $dataProcessorInstance->getTrigger(), $dataProcessorInstance->getRelatedObjectType(), $dataProcessorInstance->getRelatedObjectKey(),
            $dataProcessorInstance->getScheduledTask()?->getTimePeriods(),
            $dataProcessorInstance->getScheduledTask()?->getStatus(),
            $dataProcessorInstance->getScheduledTask()?->getLastStartTime(),
            $dataProcessorInstance->getScheduledTask()?->getLastEndTime(),
            $dataProcessorInstance->getScheduledTask()?->getNextStartTime(),
            $dataProcessorInstance->getKey()
        );
    }


}