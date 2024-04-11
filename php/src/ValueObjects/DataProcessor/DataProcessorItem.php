<?php

namespace Kinintel\ValueObjects\DataProcessor;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;

class DataProcessorItem {

    /**
     * @param string|null $title
     * @param string|null $type
     * @param mixed $config
     * @param string|null $trigger
     * @param string|null $relatedObjectType Related object type if relevant e.g. DatasetInstance, DatasourceInstance
     * @param mixed $relatedObjectPrimaryKey Primary key for related object if relevant
     * @param string|null $relatedObjectTitle
     * @param ScheduledTaskTimePeriod[] $taskTimePeriods
     * @param string|null $taskStatus
     * @param string|null $taskLastStartTime
     * @param string|null $taskLastEndTime
     * @param string|null $taskNextStartTime
     * @param string|null $key
     */
    public function __construct(private ?string $title,
                                private ?string $type,
                                private mixed $config,
                                private ?string $trigger = DataProcessorInstance::TRIGGER_ADHOC,
                                private ?string $relatedObjectType = null,
                                private ?string $relatedObjectPrimaryKey = null,
                                private ?string $relatedObjectTitle = null,
                                private $taskTimePeriods = [],
                                private ?string $taskStatus = null,
                                private ?string $taskLastStartTime = null,
                                private ?string $taskLastEndTime = null,
                                private ?string $taskNextStartTime = null,
                                private ?string $key = null) {
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
     * @return string
     */
    public function getRelatedObjectType() {
        return $this->relatedObjectType;
    }

    /**
     * @param string $relatedObjectType
     */
    public function setRelatedObjectType($relatedObjectType) {
        $this->relatedObjectType = $relatedObjectType;
    }

    /**
     * @return mixed
     */
    public function getRelatedObjectPrimaryKey() {
        return $this->relatedObjectPrimaryKey;
    }

    /**
     * @param mixed $relatedObjectPrimaryKey
     */
    public function setRelatedObjectPrimaryKey($relatedObjectPrimaryKey) {
        $this->relatedObjectPrimaryKey = $relatedObjectPrimaryKey;
    }

    /**
     * @return string
     */
    public function getRelatedObjectTitle() {
        return $this->relatedObjectTitle;
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
    public function toDataProcessorInstance(
        $projectKey = null,
        $accountId = Account::LOGGED_IN_ACCOUNT
    ) : DataProcessorInstance {

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
    public static function fromDataProcessorInstance($dataProcessorInstance) : DataProcessorItem {
        return new DataProcessorItem($dataProcessorInstance->getTitle(), $dataProcessorInstance->getType(), $dataProcessorInstance->getConfig(),
            $dataProcessorInstance->getTrigger(), $dataProcessorInstance->getRelatedObjectType(), $dataProcessorInstance->getRelatedObjectKey(), $dataProcessorInstance->getRelatedObjectTitle(),
            $dataProcessorInstance->getScheduledTask()?->getTimePeriods(),
            $dataProcessorInstance->getScheduledTask()?->getStatus(),
            $dataProcessorInstance->getScheduledTask()?->getLastStartTime()?->format("d/m/Y H:i:s"),
            $dataProcessorInstance->getScheduledTask()?->getLastEndTime()?->format("d/m/Y H:i:s"),
            $dataProcessorInstance->getScheduledTask()?->getNextStartTime()?->format("d/m/Y H:i:s"),
            $dataProcessorInstance->getKey()
        );
    }


}