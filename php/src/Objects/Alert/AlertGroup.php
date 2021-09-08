<?php


namespace Kinintel\Objects\Alert;


use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Alert group class
 *
 * @table ki_alert_group
 * @generate
 */
class AlertGroup extends ActiveRecord {
    use AccountProject;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;


    /**
     * @var ScheduledTask
     * @manyToOne
     * @parentJoinColumns scheduled_task_id
     * @saveCascade
     */
    protected $scheduledTask;

    /**
     * @var string
     */
    protected $notificationTitle;

    /**
     * @var string
     */
    protected $notificationPrefixText;

    /**
     * @var string
     */
    protected $notificationSuffixText;


    /**
     * @var NotificationGroup[]
     * @manyToMany
     * @linkTable ki_alert_group_notification_group
     */
    protected $notificationGroups;

    /**
     * AlertGroup constructor.
     * @param $title
     * @param ScheduledTask $scheduledTask
     * @param NotificationGroup[] $notificationGroups
     * @param string $projectKey
     * @param integer $accountId
     */
    public function __construct($title, $scheduledTask = null, $notificationGroups = [],
                                $notificationTitle = null, $notificationPrefixText = null, $notificationSuffixText = null,
                                $projectKey = null, $accountId = null, $id = null) {
        $this->title = $title;
        $this->scheduledTask = $scheduledTask;
        $this->notificationGroups = $notificationGroups;
        $this->notificationTitle = $notificationTitle;
        $this->notificationSuffixText = $notificationSuffixText;
        $this->notificationPrefixText = $notificationPrefixText;
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
        $this->id = $id;
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
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
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
     * @return string
     */
    public function getNotificationTitle() {
        return $this->notificationTitle;
    }

    /**
     * @param string $notificationTitle
     */
    public function setNotificationTitle($notificationTitle) {
        $this->notificationTitle = $notificationTitle;
    }

    /**
     * @return string
     */
    public function getNotificationPrefixText() {
        return $this->notificationPrefixText;
    }

    /**
     * @param string $notificationPrefixText
     */
    public function setNotificationPrefixText($notificationPrefixText) {
        $this->notificationPrefixText = $notificationPrefixText;
    }

    /**
     * @return string
     */
    public function getNotificationSuffixText() {
        return $this->notificationSuffixText;
    }

    /**
     * @param string $notificationSuffixText
     */
    public function setNotificationSuffixText($notificationSuffixText) {
        $this->notificationSuffixText = $notificationSuffixText;
    }

    /**
     * @return NotificationGroup[]
     */
    public function getNotificationGroups() {
        return $this->notificationGroups;
    }

    /**
     * @param NotificationGroup[] $notificationGroups
     */
    public function setNotificationGroups($notificationGroups) {
        $this->notificationGroups = $notificationGroups;
    }


    /**
     * Return a summary object converting any subordinates to summaries as well
     */
    public function returnSummary() {

        $notificationGroupSummaries = [];
        foreach ($this->notificationGroups ?? [] as $notificationGroup) {
            $notificationGroupSummaries[] = $notificationGroup->returnSummary();
        }

        if (!$this->scheduledTask) {
            $this->scheduledTask = new ScheduledTask(null);
        }

        return new AlertGroupSummary($this->title, $this->scheduledTask->getTimePeriods(), $notificationGroupSummaries,
            $this->notificationTitle, $this->notificationPrefixText, $this->notificationSuffixText,
            $this->scheduledTask->getStatus(), $this->scheduledTask->getLastStartTime(), $this->scheduledTask->getLastEndTime(),
            $this->scheduledTask->getNextStartTime(), $this->id);
    }

}