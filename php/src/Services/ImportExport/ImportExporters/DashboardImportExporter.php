<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\ValueObjects\ImportExport\ExportConfig\DashboardExportConfig;

class DashboardImportExporter extends ImportExporter {

    /**
     * Construct with dashboard service
     *
     * @param DashboardService $dashboardService
     */
    public function __construct(private DashboardService $dashboardService) {
    }

    public function getObjectTypeCollectionIdentifier() {
        return "dashboards";
    }

    public function getObjectTypeCollectionTitle() {
        return "Dashboards";
    }

    public function getObjectTypeImportClassName() {
        return DashboardSummary::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return DashboardExportConfig::class;
    }

    /**
     * Get potential dashboards for export
     *
     * @param int $accountId
     * @param string $projectKey
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {

        return array_map(function ($dashboardSummary) {
            return new ProjectExportResource($dashboardSummary->getId(), $dashboardSummary->getTitle(), new DashboardExportConfig());
        }, $this->dashboardService->getAllDashboards($projectKey, $accountId));
    }

    /**
     * Create dashboard export objects.
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     * @param mixed $allProjectExportConfig
     * @return void
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {

        // Grab all project dashboards
        $allProjectDashboards = ObjectArrayUtils::indexArrayOfObjectsByMember("id", $this->dashboardService->getAllDashboards($projectKey, $accountId));

        $exportableItems = [];
        foreach ($objectExportConfig as $key => $config) {
            // If included continue
            if ($config->isIncluded()) {

                $exportItem = $allProjectDashboards[$key];

                // Update id
                $exportItem->setId(self::getNewExportPK("dashboards", $exportItem->getId()));

                if ($exportItem->getParentDashboardId())
                    $exportItem->setParentDashboardId(self::getNewExportPK("dashboards", $exportItem->getParentDashboardId()));

                // Loop through dataset instances
                foreach ($exportItem->getDatasetInstances() as $datasetInstance) {

                    // Remap source fields to match already exported items.
                    $datasetInstance->setDatasourceInstanceKey(self::remapExportObjectPK("datasources", $datasetInstance->getDatasourceInstanceKey()));
                    $datasetInstance->setDatasetInstanceId(self::remapExportObjectPK("datasets", $datasetInstance->getDatasetInstanceId()));
                    $datasetInstance->setDashboardId($exportItem->getId());

                    // Remap any transformations
                    foreach ($datasetInstance->getTransformationInstances() ?? [] as $transformationInstance) {
                        $transformationConfig = $transformationInstance->getConfig();
                        switch ($transformationInstance->getType()) {
                            case "join":
                                $transformationConfig->setJoinedDataSourceInstanceKey(self::remapExportObjectPK("datasources", $transformationConfig->getJoinedDataSourceInstanceKey()));
                                $transformationConfig->setJoinedDataSetInstanceId(self::remapExportObjectPK("datasets", $transformationConfig->getJoinedDataSetInstanceId()));
                                break;
                            case "combine":
                                $transformationConfig->setCombinedDataSourceInstanceKey(self::remapExportObjectPK("datasources", $transformationConfig->getCombinedDataSourceInstanceKey()));
                                $transformationConfig->setCombinedDataSetInstanceId(self::remapExportObjectPK("datasets", $transformationConfig->getCombinedDataSetInstanceId()));
                                break;
                        }
                        $transformationInstance->setConfig($transformationConfig);
                    }


                    // Deal with alerts
                    if ($config->isIncludeAlerts()) {
                        foreach ($datasetInstance->getAlerts() as $alert) {
                            $alert->setId(null);
                            $alert->setAlertGroupId(self::remapExportObjectPK("alertGroups", $alert->getAlertGroupId()));
                        }
                    } else {
                        $datasetInstance->setAlerts([]);
                    }

                }

                $exportableItems[] = $exportItem;
            }
        }

        return $exportableItems;

    }

    /**
     * Analyse import
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     *
     * @return ProjectImportResource[]
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        // Get account members
        $accountItems = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->dashboardService->getAllDashboards($projectKey, $accountId));


        /**
         * Loop through config and create import resources
         */
        $importResources = [];
        foreach ($exportObjects as $exportObject) {
            $configuration = $objectExportConfig[$exportObject->getId()];
            $accountItem = $accountItems[$exportObject->getTitle()] ?? null;
            if ($configuration->isIncluded()) {
                $importResources[] = new ProjectImportResource($exportObject->getId(), $exportObject->getTitle(),
                    $accountItem ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Create,
                    $accountItem?->getId());
            }
        }

        return $importResources;

    }

    /**
     * Import objects
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {


        // Get account members
        $accountItems = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->dashboardService->getAllDashboards($projectKey, $accountId));


        /**
         * Loop through config and create import resources
         */
        $dashboardsWithParents = [];
        foreach ($exportObjects as $exportObject) {

            $importId = $exportObject->getId();
            $configuration = $objectExportConfig[$exportObject->getId()];
            $accountItem = $accountItems[$exportObject->getTitle()] ?? null;

            if ($configuration->isIncluded()) {

                // Deal with existing and new items.
                if ($accountItem) {
                    $exportObject->setId($accountItem->getId());
                    $existingDatasetInstancesByInstanceId = ObjectArrayUtils::indexArrayOfObjectsByMember("instanceKey", $accountItem->getDatasetInstances() ?? []);
                } else {
                    $existingDatasetInstancesByInstanceId = [];
                    $exportObject->setId(null);
                }

                // Process dataset instances
                foreach ($exportObject->getDatasetInstances() as $datasetInstance) {

                    // Remap source objects
                    $datasetInstance->setDatasetInstanceId(self::remapImportedItemId("datasets", $datasetInstance->getDatasetInstanceId()));
                    $datasetInstance->setDatasourceInstanceKey(self::remapImportedItemId("datasources", $datasetInstance->getDatasourceInstanceKey()));
                    $datasetInstance->setDashboardId(null);

                    // Remap any transformations
                    foreach ($datasetInstance->getTransformationInstances() ?? [] as $transformationInstance) {
                        $transformationConfig = $transformationInstance->getConfig();
                        switch ($transformationInstance->getType()) {
                            case "join":
                                $transformationConfig->setJoinedDataSourceInstanceKey(self::remapImportedItemId("datasources", $transformationConfig->getJoinedDataSourceInstanceKey()));
                                $transformationConfig->setJoinedDataSetInstanceId(self::remapImportedItemId("datasets", $transformationConfig->getJoinedDataSetInstanceId()));
                                break;
                            case "combine":
                                $transformationConfig->setCombinedDataSourceInstanceKey(self::remapImportedItemId("datasources", $transformationConfig->getCombinedDataSourceInstanceKey()));
                                $transformationConfig->setCombinedDataSetInstanceId(self::remapImportedItemId("datasets", $transformationConfig->getCombinedDataSetInstanceId()));
                                break;
                        }
                        $transformationInstance->setConfig($transformationConfig);

                    }

                    // If we are including alerts and not updating templates ensure we keep original templates intact
                    if ($configuration->isIncludeAlerts()) {

                        $accountItemDatasetInstance = $existingDatasetInstancesByInstanceId[$datasetInstance->getInstanceKey()] ?? null;
                        $accountItemAlerts = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $accountItemDatasetInstance?->getAlerts() ?? []);

                        foreach ($datasetInstance->getAlerts() ?? [] as $alert) {

                            // Remap alert group id
                            $alert->setAlertGroupId(self::remapImportedItemId("alertGroups", $alert->getAlertGroupId()));

                            // Resync the template data from original if not updating
                            if (!$configuration->isUpdateAlertTemplates()) {
                                $accountItemAlert = $accountItemAlerts[$alert->getTitle()] ?? null;
                                if ($accountItemAlert) {
                                    $alert->setNotificationTemplate($accountItemAlert->getNotificationTemplate());
                                    $alert->setSummaryTemplate($accountItemAlert->getSummaryTemplate());
                                    $alert->setNotificationCta($accountItemAlert->getNotificationCta());
                                }
                            }
                        }

                    }

                }



                $savedDashboardId = $this->dashboardService->saveDashboard($exportObject, $projectKey, $accountId);

                if ($exportObject->getParentDashboardId())
                    $dashboardsWithParents[] = $savedDashboardId;

                self::setImportItemIdMapping("dashboards", $importId, $savedDashboardId);

            }

        }


        // Now loop through the dashboards with parents and update parent IDs
        foreach ($dashboardsWithParents as $dashboardWithParent) {
            $dashboard = $this->dashboardService->getShallowDashboard($dashboardWithParent);
            $dashboard->setParentDashboardId(self::remapImportedItemId("dashboards", $dashboard->getParentDashboardId()));
            $this->dashboardService->saveDashboard($dashboard, $projectKey, $accountId);
        }


    }
}