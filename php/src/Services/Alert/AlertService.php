<?php


namespace Kinintel\Services\Alert;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Alert\AlertGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;

class AlertService {


    /**
     * List the alert groups for a specified project and account
     * using offset and limit accordingly
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     * @param string $projectKey
     * @param int $accountId
     */
    public function listAlertGroups($filterString = "", $limit = 25, $offset = 0, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $query = "WHERE accountId = ?";
        $params = [$accountId];

        if ($projectKey) {
            $query .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        if ($filterString) {
            $query .= " AND title like ?";
            $params[] = "%$filterString%";
        }

        $query .= " ORDER BY title ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return array_map(function ($instance) {
            return $instance->returnSummary();
        }, AlertGroup::filter($query, $params));

    }


    /**
     * Get a single alert group by primary key
     *
     * @param int $alertGroupId
     *
     * @return AlertGroupSummary
     */
    public function getAlertGroup($alertGroupId) {
        $alertGroup = AlertGroup::fetch($alertGroupId);
        return $alertGroup->returnSummary();
    }

    /**
     * Save an alert group
     *
     * @param AlertGroupSummary $alertGroupSummary
     * @param string $projectKey
     * @param string $accountId
     */
    public function saveAlertGroup($alertGroupSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        if ($alertGroupSummary->getId()) {
            $alertGroup = AlertGroup::fetch($alertGroupSummary->getId());
            $alertGroup->setTitle($alertGroupSummary->getTitle());
            $alertGroup->getScheduledTask()->setTimePeriods($alertGroupSummary->getTaskTimePeriods());
        } else {

            // Create a basic one firstly
            $alertGroup = new AlertGroup($alertGroupSummary->getTitle(), null, [], $projectKey, $accountId);
            $alertGroup->save();

            // Now set up the subordinate objects
            $alertGroup->setScheduledTask(new ScheduledTask(new ScheduledTaskSummary("alertgroup",
                "Alert Group: " . $alertGroup->getTitle() . " (Account " . $accountId . ")",
                [
                    "alertGroupId" => $alertGroup->getId()
                ], $alertGroupSummary->getTaskTimePeriods()), $projectKey, $accountId));

        }


        $notificationGroups = [];
        foreach ($alertGroupSummary->getNotificationGroups() as $notificationGroupSummary) {
            $notificationGroups[] = new NotificationGroup($notificationGroupSummary, $projectKey, $accountId);
        }
        $alertGroup->setNotificationGroups($notificationGroups);

        // Save again at the end.
        $alertGroup->save();

        // Get the alert group id
        return $alertGroup->getId();

    }


    /**
     * Delete the alert group with the specified key
     *
     * @param integer $alertGroupId
     */
    public function deleteAlertGroup($alertGroupId) {

        $alertGroup = AlertGroup::fetch($alertGroupId);
        $alertGroup->remove();

    }

}