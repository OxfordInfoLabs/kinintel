<?php


namespace Kinintel\Services\Alert;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kinintel\Objects\Alert\AlertGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;

class AlertService {


    /**
     * List the alert groups for a specified project and account
     * using offset and limit accordingly
     *
     * @param int $limit
     * @param int $offset
     * @param string $projectKey
     * @param int $accountId
     */
    public function listAlertGroups($limit = 25, $offset = 0, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

    }


    /**
     * Get a single alert group by primary key
     *
     * @param int $alertGroupId
     */
    public function getAlertGroup($alertGroupId) {

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

        } else {

            // Create a basic one firstly
            $alertGroup = new AlertGroup($alertGroupSummary->getTitle(), null, [], $projectKey, $accountId);
            $alertGroup->save();

            // Now set up the subordinate objects

        }


        // Get the alert group id
        return $alertGroup->getId();

    }


    /**
     * Delete the alert group with the specified key
     *
     * @param integer $alertGroupId
     */
    public function deleteAlertGroup($alertGroupId) {

    }

}