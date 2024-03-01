<?php

namespace Kinintel\ValueObjects\DataProcessor;

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
     * @param ScheduledTaskTimePeriod[] $taskTimePeriods
     * @param string $taskStatus
     * @param string $taskLastStartTime
     * @param string $taskLastEndTime
     * @param string $taskNextStartTime
     * @param string $key
     */
    public function __construct($title, $type, $config, $trigger = DataProcessorInstance::TRIGGER_ADHOC, $taskTimePeriods = [], $taskStatus = null, $taskLastStartTime = null, $taskLastEndTime = null, $taskNextStartTime = null, $key = null) {
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


}