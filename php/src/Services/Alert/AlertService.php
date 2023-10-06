<?php


namespace Kinintel\Services\Alert;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Alert\AlertGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
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
     * @var AccountService
     */
    private $accountService;

    /**
     * @var SecurityService
     */
    private $securityService;


    /**
     * @var ActiveRecordInterceptor
     */
    private $activeRecordInterceptor;

    /**
     * @var FileResolver
     */
    private $fileResolver;


    /**
     * AlertService constructor.
     *
     * @param DashboardService $dashboardService
     * @param NotificationService $notificationService
     * @param TemplateParser $templateParser
     * @param AccountService $accountService
     * @param SecurityService $securityService
     * @param ActiveRecordInterceptor $activeRecordInterceptor
     * @param FileResolver $fileResolver
     */
    public function __construct($dashboardService, $notificationService, $templateParser, $accountService, $securityService, $activeRecordInterceptor, $fileResolver) {
        $this->dashboardService = $dashboardService;
        $this->notificationService = $notificationService;
        $this->templateParser = $templateParser;
        $this->accountService = $accountService;
        $this->securityService = $securityService;
        $this->activeRecordInterceptor = $activeRecordInterceptor;
        $this->fileResolver = $fileResolver;
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

        // Ensure we log in as alert group user
        $this->activeRecordInterceptor->executeInsecure(function () use ($alertGroupId) {

            // Grab alert group
            $alertGroup = AlertGroup::fetch($alertGroupId);

            // If an account id, login as account id.
            if ($alertGroup->getAccountId()) {
                $this->securityService->becomeAccount($alertGroup->getAccountId());
            } else {
                $this->securityService->becomeSuperUser();
            }

        });

        // Grab the active alerts
        $activeAlerts = $this->dashboardService->getActiveDashboardDatasetAlertsMatchingAlertGroup($alertGroupId);

        // Loop through each active alert
        $evaluatedAlerts = [];
        foreach ($activeAlerts as $activeAlert) {

            $dashboardDatasetInstance = $activeAlert->getDashboardDatasetInstance();

            // Process each alert for the dataset
            foreach ($activeAlert->getAlerts() as $alert) {
                $result = $this->processAlertForDashboardDatasetInstance($dashboardDatasetInstance, $alert);
                if ($result) {
                    $evaluatedAlerts[] = $result;
                }
            }

        }

        // If at least one alert message
        if (sizeof($evaluatedAlerts)) {


            /**
             * @var AlertGroup $alertGroup
             */
            $alertGroup = AlertGroup::fetch($alertGroupId);

            // Initialise the template model
            $alertTemplateModel = ["alerts" => $evaluatedAlerts,
                "prefixText" => $alertGroup->getNotificationPrefixText() ?? "",
                "suffixText" => $alertGroup->getNotificationSuffixText() ?? ""];


            $alertTemplate = $this->fileResolver->resolveFile("Config/alert-template.html");
            $alertText = $this->templateParser->parseTemplateText(file_get_contents($alertTemplate), $alertTemplateModel);


            $notificationSummary = new NotificationSummary($alertGroup->getNotificationTitle(),
                $alertText, null, $alertGroup->getNotificationGroups(), null,
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

            if ($alert->isEnabled() && ($alertIndex === null || $index == $alertIndex)) {
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


        $newDataset = new ArrayTabularDataset($evaluatedDataset->getColumns(), $evaluatedDataset->getAllData());

        // If the rule matches, append the evaluated template
        // To the list of messages.
        if ($alert->evaluateMatchRule($newDataset)) {

            $dataSetData = $newDataset->getAllData();

            $templateData = [
                "rowCount" => sizeof($dataSetData),
                "data" => $dataSetData
            ];

            // Generate messages
            $notificationMessage = $this->templateParser->parseTemplateText($alert->getNotificationTemplate(), $templateData);
            $notificationCta = $this->templateParser->parseTemplateText($alert->getNotificationCta(), $templateData);
            $summaryMessage = $this->templateParser->parseTemplateText($alert->getSummaryTemplate(), $templateData);

            // Evaluate alert message using template parser
            return [
                "title" => $alert->getTitle(),
                "notificationMessage" => $notificationMessage,
                "notificationCta" => $notificationCta,
                "summaryMessage" => $summaryMessage
            ];

        } else {
            return false;
        }
    }


}