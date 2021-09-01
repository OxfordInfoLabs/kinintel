<?php


namespace Kinintel\Objects\Alert;


use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Alert group - combines multiple alerts together with a check frequency
 *
 * Class AlertGroupSummary
 * @package Kinintel\Objects\Alert
 *
 */
class AlertGroupSummary {

    /**
     * @var integer
     * @primaryKey
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;


    /**
     * @var ScheduledTaskTimePeriod[]
     */
    protected $taskTimePeriods;


    /**
     * @var string
     */
    protected $taskStatus;

    /**
     * @var string
     */
    protected $taskLastStartTime;

    /**
     * @var string
     */
    protected $taskLastEndTime;


    /**
     * @var string
     */
    protected $taskNextStartTime;


    /**
     * @var NotificationGroupSummary[]
     */
    protected $notificationGroups;

    /**
     * AlertGroupSummary constructor.
     *
     * @param string $title
     * @param ScheduledTaskTimePeriod[] $taskTimePeriods
     * @param NotificationGroupSummary[] $notificationGroups
     * @param string $taskStatus
     * @param string $taskLastStartTime
     * @param string $taskLastEndTime
     * @param string $nextStartTime
     * @param integer $id
     */
    public function __construct($title, $taskTimePeriods = [], $notificationGroups = [], $taskStatus = null, $taskLastStartTime = null, $taskLastEndTime = null, $nextStartTime = null, $id = null) {
        $this->id = $id;
        $this->title = $title;
        $this->taskTimePeriods = $taskTimePeriods;
        $this->taskStatus = $taskStatus;
        $this->taskLastStartTime = $taskLastStartTime;
        $this->taskLastEndTime = $taskLastEndTime;
        $this->taskNextStartTime = $nextStartTime;
        $this->notificationGroups = $notificationGroups;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return ScheduledTaskTimePeriod[]
     */
    public function getTaskTimePeriods() {
        return $this->taskTimePeriods;
    }

    /**
     * @return NotificationGroupSummary[]
     */
    public function getNotificationGroups() {
        return $this->notificationGroups;
    }

    /**
     * @return string
     */
    public function getTaskStatus() {
        return $this->taskStatus;
    }

    /**
     * @return string
     */
    public function getTaskLastStartTime() {
        return $this->taskLastStartTime;
    }

    /**
     * @return string
     */
    public function getTaskLastEndTime() {
        return $this->taskLastEndTime;
    }

    /**
     * @return string
     */
    public function getTaskNextStartTime() {
        return $this->taskNextStartTime;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @param ScheduledTaskTimePeriod[] $taskTimePeriods
     */
    public function setTaskTimePeriods($taskTimePeriods) {
        $this->taskTimePeriods = $taskTimePeriods;
    }

    /**
     * @param NotificationGroupSummary[] $notificationGroups
     */
    public function setNotificationGroups($notificationGroups) {
        $this->notificationGroups = $notificationGroups;
    }


}