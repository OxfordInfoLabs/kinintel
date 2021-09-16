<?php


namespace Kinintel\Services\Alert;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kinikit\Core\Template\TemplateParser;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Alert\AlertGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class AlertService {


    /**
     * @var DashboardService
     */
    private $dashboardService;

    /**
     * @var NotificationService
     */
    private $notificationService;


    /**
     * @var TemplateParser
     */
    private $templateParser;


    /**
     * AlertService constructor.
     *
     * @param DashboardService $dashboardService
     * @param NotificationService $notificationService
     * @param TemplateParser $templateParser
     */
    public function __construct($dashboardService, $notificationService, $templateParser) {
        $this->dashboardService = $dashboardService;
        $this->notificationService = $notificationService;
        $this->templateParser = $templateParser;
    }

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
            $alertGroup->setNotificationTitle($alertGroupSummary->getNotificationTitle());
            $alertGroup->setNotificationPrefixText($alertGroupSummary->getNotificationPrefixText());
            $alertGroup->setNotificationSuffixText($alertGroupSummary->getNotificationSuffixText());
            $alertGroup->setTitle($alertGroupSummary->getTitle());

            $alertGroup->getScheduledTask()->setTimePeriods($alertGroupSummary->getTaskTimePeriods());
        } else {

            // Create a basic one firstly
            $alertGroup = new AlertGroup($alertGroupSummary->getTitle(), null, [], $alertGroupSummary->getNotificationTitle(),
                $alertGroupSummary->getNotificationPrefixText(), $alertGroupSummary->getNotificationSuffixText(),
                $alertGroupSummary->getNotificationLevel(),
                $projectKey, $accountId);
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


    /**
     * Process all alerts for an alert group supplied by id.
     *
     * @param $alertGroupId
     */
    public function processAlertGroup($alertGroupId) {

        // Grab the active alerts
        $activeAlerts = $this->dashboardService->getActiveDashboardDatasetAlertsMatchingAlertGroup($alertGroupId);

        // Loop through each active alert
        $alertMessageStrings = [];
        foreach ($activeAlerts as $activeAlert) {

            $dashboardDatasetInstance = $activeAlert->getDashboardDatasetInstance();

            // Process each alert for the dataset
            foreach ($activeAlert->getAlerts() as $alert) {

                $alertMessages = $this->processAlertForDashboardDatasetInstance($dashboardDatasetInstance, $alert);
                if ($alertMessages["notificationMessage"] ?? null) {
                    $alertMessageStrings[] = $alertMessages["notificationMessage"];
                }

            }

        }

        // If at least one alert message
        if (sizeof($alertMessageStrings)) {

            /**
             * @var AlertGroup $alertGroup
             */
            $alertGroup = AlertGroup::fetch($alertGroupId);

            // If prefix text prepend it
            if ($alertGroup->getNotificationPrefixText()) {
                array_unshift($alertMessageStrings, $alertGroup->getNotificationPrefixText());
            }

            // If suffix text append it
            if ($alertGroup->getNotificationSuffixText()) {
                $alertMessageStrings[] = $alertGroup->getNotificationSuffixText();
            }

            $notificationSummary = new NotificationSummary($alertGroup->getNotificationTitle(),
                join("\n", $alertMessageStrings), null, $alertGroup->getNotificationGroups(), null,
                $alertGroup->getNotificationLevel());

            $this->notificationService->createNotification($notificationSummary, $alertGroup->getProjectKey(), $alertGroup->getAccountId());
        }


    }


    /**
     * Process alerts for a dashboard dataset instance - optionally limited to a single alert.
     *
     * @param DashboardDatasetInstance $dashboardDataSetInstance
     * @param integer $alertIndex
     */
    public function processAlertsForDashboardDatasetInstance($dashboardDataSetInstance, $alertIndex = null) {

        $alertObjects = [];
        foreach ($dashboardDataSetInstance->getAlerts() as $index => $alert) {

            if ($alertIndex === null || $index == $alertIndex) {
                $alertMessages = $this->processAlertForDashboardDatasetInstance($dashboardDataSetInstance, $alert);

                if ($alertMessages) {
                    $alertObjects[] = array_merge($alertMessages, ["alertIndex" => $index]);
                }
            }
        }

        return $alertObjects;

    }


    /**
     * @param DashboardDatasetInstance $dashboardDatasetInstance
     * @param Alert $alert
     */
    private function processAlertForDashboardDatasetInstance($dashboardDatasetInstance, $alert) {
        $evaluatedDataset = $this->dashboardService->getEvaluatedDataSetForDashboardDataSetInstanceObject($dashboardDatasetInstance,
            $alert->getFilterTransformation() ? [new TransformationInstance("filter", $alert->getFilterTransformation())] : []);

        // If the rule matches, append the evaluated template
        // To the list of messages.
        if ($alert->evaluateMatchRule($evaluatedDataset)) {

            $dataSetData = $evaluatedDataset->getAllData();

            $templateData = [
                "rowCount" => sizeof($dataSetData),
                "data" => $dataSetData
            ];

            // Evaluate alert message using template parser
            return [
                "notificationMessage" => $this->templateParser->parseTemplateText($alert->getNotificationTemplate(), $templateData),
                "summaryMessage" => $this->templateParser->parseTemplateText($alert->getSummaryTemplate(), $templateData)
            ];
        } else {
            return false;
        }
    }


}