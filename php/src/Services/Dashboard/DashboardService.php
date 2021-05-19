<?php

namespace Kinintel\Services\Dashboard;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\MetaData\MetaDataService;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Services\Dataset\DatasetService;
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
     * DashboardService constructor.
     *
     * @param DatasetService $datasetService
     * @param MetaDataService $metaDataService
     */
    public function __construct($datasetService, $metaDataService) {
        $this->datasetService = $datasetService;
        $this->metaDataService = $metaDataService;
    }


    /**
     * Get a dashboard by id
     *
     * @param $id
     * @return DashboardSummary
     */
    public function getDashboardById($id) {
        return Dashboard::fetch($id)->returnSummary();
    }

    /**
     * Save a dashboard
     *
     * @param DashboardSummary $dashboard
     */
    public function saveDashboard($dashboardSummary, $accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {
        $dashboard = new Dashboard($dashboardSummary, $accountId, $projectKey);

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


}