<?php

namespace Kinintel\Services\Dashboard;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Services\Security\SecurityService;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSearchResult;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\ValueObjects\Alert\ActiveDashboardDatasetAlerts;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * Main entry point for access to dashboard related functions
 *
 * Class DashboardService
 */
class DashboardService {

    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * @var MetaDataService
     */
    private $metaDataService;


    /**
     * @var SecurityService
     */
    private $securityService;

    /**
     * DashboardService constructor.
     *
     * @param DatasetService $datasetService
     * @param MetaDataService $metaDataService
     * @param SecurityService $securityService
     */
    public function __construct($datasetService, $metaDataService, $securityService) {
        $this->datasetService = $datasetService;
        $this->metaDataService = $metaDataService;
        $this->securityService = $securityService;
    }


    /**
     * Get a dashboard by id
     *
     * @param $id
     * @return DashboardSummary
     */
    public function getDashboardById($id) {
        $dashboard = Dashboard::fetch($id);

        $returnCopy = $dashboard->getAccountId() == null && !$this->securityService->isSuperUserLoggedIn();
        $summary = $dashboard->returnSummary($returnCopy);

        return $summary;
    }


    /**
     * Filter dashboards optionally by title, tags, project key and limiting as required
     *
     * @param string $filterString
     * @param array $tags
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param string $accountId
     *
     * @return DashboardSearchResult[]
     */
    public function filterDashboards($filterString = "", $tags = [], $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $params = [];
        if ($accountId === null) {
            $query = "WHERE accountId IS NULL";
        } else {
            $query = "WHERE accountId = ?";
            $params[] = $accountId;
        }

        if ($filterString) {
            $query .= " AND title LIKE ?";
            $params[] = "%$filterString%";
        }

        if ($projectKey) {
            $query .= " AND project_key = ?";
            $params[] = $projectKey;
        }

        if ($tags && sizeof($tags) > 0) {
            $query .= " AND tags.tag_key IN (" . str_repeat("?", sizeof($tags)) . ")";
            $params = array_merge($params, $tags);
        }


        $query .= " ORDER BY title LIMIT $limit OFFSET $offset";

        // Return a summary array
        return array_map(function ($instance) {
            return new DashboardSearchResult($instance->getId(), $instance->getTitle());
        },
            Dashboard::filter($query, $params));


    }


    /**
     * Save a dashboard
     *
     * @param DashboardSummary $dashboard
     */
    public function saveDashboard($dashboardSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $dashboard = new Dashboard($dashboardSummary, $accountId, $projectKey);
        Logger::log($dashboardSummary);
        // Process tags
        if (sizeof($dashboardSummary->getTags())) {
            $tags = $this->metaDataService->getObjectTagsFromSummaries($dashboardSummary->getTags(), $accountId, $projectKey);
            $dashboard->setTags($tags);
        }

        $dashboard->save();
        return $dashboard->getId();
    }


    /**
     * Remove a dashboard by id
     *
     * @param $dashboardId
     */
    public function removeDashboard($dashboardId) {
        $dashboard = Dashboard::fetch($dashboardId);
        $dashboard->remove();
    }


    /**
     * Get the evaluated data set for a dashboard and data set instance referenced by key
     *
     * @param integer $dashboardId
     * @param string $datasetInstanceKey
     * @param TransformationInstance[] $additionalTransformations
     */
    public function getEvaluatedDataSetForDashboardDataSetInstance($dashboardId, $datasetInstanceKey, $additionalTransformations = []) {

        /**
         * @var DashboardDatasetInstance $dashboardDatasetInstance
         */
        $dashboardDatasetInstance = DashboardDatasetInstance::fetch([$dashboardId, $datasetInstanceKey]);

        // If a dataset instance id in use, return using appropriate call
        if ($dashboardDatasetInstance->getDatasetInstanceId()) {
            return $this->datasetService->getEvaluatedDataSetForDataSetInstanceById($dashboardDatasetInstance->getDatasetInstanceId(), $additionalTransformations);
        } else {
            return $this->datasetService->getEvaluatedDataSetForDataSetInstance($dashboardDatasetInstance, $additionalTransformations);
        }

    }


    /**
     * Get all dashboards containing alerts which match the supplied group id.
     *
     * @param $alertGroupId
     * @return ActiveDashboardDatasetAlerts[]
     */
    public function getActiveDashboardDatasetAlertsMatchingAlertGroup($alertGroupId) {

        /**
         * Get all dashboards with dataset alerts which match the alert group
         * @var Dashboard[] $matchingDashboards
         */
        $matchingDashboards = Dashboard::filter("WHERE alertsEnabled 
                AND datasetInstances.alerts.alert_group_id = ?", $alertGroupId);

        $activeDashboardDatasetAlerts = [];
        foreach ($matchingDashboards as $dashboard) {
            foreach ($dashboard->getDatasetInstances() as $datasetInstance) {
                $activeAlerts = [];
                foreach ($datasetInstance->getAlerts() as $alert) {
                    if ($alert->isEnabled() && $alert->getAlertGroupId() == $alertGroupId)
                        $activeAlerts[] = $alert;
                }
                if (sizeof($activeAlerts)) {
                    $activeDashboardDatasetAlerts[] = new ActiveDashboardDatasetAlerts($datasetInstance, $activeAlerts);
                }
            }
        }

        return $activeDashboardDatasetAlerts;
    }


}
