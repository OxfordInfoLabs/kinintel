<?php


namespace Kinintel\Services\ImportExport;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Account\ProjectService;
use Kinintel\Services\Alert\AlertService;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\ImportExport\Export;
use Kinintel\ValueObjects\ImportExport\ExportableResources;
use Kinintel\ValueObjects\ImportExport\ResourceExportDescriptor;

/**
 * General export service for exporting resources.
 *
 * Class ExportService
 * @package Kinintel\Services\Export
 */
class ExportService {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * @var DashboardService
     */
    private $dashboardService;

    /**
     * @var AlertService
     */
    private $alertService;

    /**
     * @var ProjectService
     */
    private $projectService;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * ExportService constructor.
     *
     * @param DatasourceService $datasourceService
     * @param DatasetService $datasetService
     * @param DashboardService $dashboardService
     * @param AlertService $alertService
     * @param ProjectService $projectService
     * @param AccountService $accountService
     */
    public function __construct($datasourceService, $datasetService, $dashboardService, $alertService, $projectService, $accountService) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
        $this->dashboardService = $dashboardService;
        $this->alertService = $alertService;
        $this->projectService = $projectService;
        $this->accountService = $accountService;
    }


    /**
     * Get exportable resources
     *
     * @param string $projectKey
     * @param string $accountId
     *
     * @return ExportableResources
     */
    public function getExportableResources($projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Gather exportable resources from various places
        $exportableDatasources = $this->datasourceService->filterDatasourceInstances("", PHP_INT_MAX, 0, false, $projectKey, $accountId);
        $exportableDatasets = $this->datasetService->filterDataSetInstances("", [], [], $projectKey, 0, PHP_INT_MAX, $accountId);
        $exportableDashboards = $this->dashboardService->getAllDashboards($projectKey, $accountId);

        return new ExportableResources($exportableDatasources, $exportableDatasets, $exportableDashboards);


    }


    /**
     * Export all resources identified by the supplied resource descriptor
     *
     * @param ResourceExportDescriptor $resourceExportDescriptor
     */
    public function exportResources($resourceExportDescriptor, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $projectScope = ($resourceExportDescriptor->getScope() == Export::SCOPE_PROJECT);

        // Resolve title
        $title = $resourceExportDescriptor->getTitle();
        if (!$title) {
            if ($projectScope) {
                $projectSummary = $this->projectService->getProject($resourceExportDescriptor->getScopeId(), $accountId);
                $title = $projectSummary->getName();
            } else {
                $accountSummary = $this->accountService->getAccountSummary($resourceExportDescriptor->getScopeId());
                $title = $accountSummary->getName();
            }
        }

        // Get and add datasourceInstances
        $datasourceInstances = [];
        foreach ($resourceExportDescriptor->getDatasourceInstanceKeys() ?? [] as $datasourceInstanceKey) {
            $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($datasourceInstanceKey);
            if ($datasourceInstance->getAccountId() == $accountId && (!$projectScope || ($resourceExportDescriptor->getScopeId() == $datasourceInstance->getProjectKey()))) {
                $datasourceInstances[] = $datasourceInstance;
            }
        }

        // Get and add dataset instances
        $datasetInstances = [];
        foreach ($resourceExportDescriptor->getDatasetInstanceIds() ?? [] as $datasetInstanceId) {
            $datasetInstance = $this->datasetService->getFullDataSetInstance($datasetInstanceId);
            if ($datasetInstance->getAccountId() == $accountId && (!$projectScope || ($resourceExportDescriptor->getScopeId() == $datasetInstance->getProjectKey()))) {
                $datasetInstances[] = $datasetInstance;
            }
        }


        // Get and add dashboards
        $dashboards = [];
        foreach ($resourceExportDescriptor->getDashboardIds() ?? [] as $dashboardId) {
            $dashboard = $this->dashboardService->getFullDashboard($dashboardId);
            if ($dashboard->getAccountId() == $accountId && (!$projectScope || ($resourceExportDescriptor->getScopeId() == $dashboard->getProjectKey()))) {
                $dashboards[] = $dashboard;
            }
        }

        // Get all scoped alert groups
        $alertGroups = $this->alertService->listAlertGroups("", PHP_INT_MAX, 0, $projectScope ? $resourceExportDescriptor->getScopeId() : null, $accountId);

        // Get all Feeds

        // Return the new export.
        return new Export($resourceExportDescriptor->getScope(), $title, $datasourceInstances, $datasetInstances, $dashboards, $alertGroups, $resourceExportDescriptor->getVersion());

    }

}