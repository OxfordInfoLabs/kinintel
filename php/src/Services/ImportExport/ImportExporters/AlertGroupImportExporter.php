<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Services\Alert\AlertService;
use Kinintel\ValueObjects\ImportExport\ExportConfig\ObjectUpdateSelectionExportConfig;

class AlertGroupImportExporter extends ImportExporter {

    /**
     * Construct with alert service
     *
     * @param AlertService $alertService
     */
    public function __construct(private AlertService $alertService) {
    }

    public function getObjectTypeCollectionIdentifier() {
        return "alertGroups";
    }

    public function getObjectTypeCollectionTitle() {
        return "Alert Groups";
    }

    public function getObjectTypeImportClassName() {
        return AlertGroupSummary::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return ObjectUpdateSelectionExportConfig::class;
    }

    /**
     * Get the exportable resources
     *
     * @param int $accountId
     * @param string $projectKey
     * @return \Kiniauth\Services\ImportExport\ProjectExportResource[]|ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        return array_map(function ($group) {
            return new ProjectExportResource($group->getId(), $group->getTitle(), new ObjectUpdateSelectionExportConfig(true, true));
        }, $this->alertService->listAlertGroups("", PHP_INT_MAX, 0, $projectKey, $accountId));

    }

    /**
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     *
     * @return mixed[]
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {

        // Grab all notification groups
        return array_filter($this->alertService->listAlertGroups("", PHP_INT_MAX, 0, $projectKey, $accountId),
            function ($item) use ($objectExportConfig) {
                if ((($objectExportConfig[$item->getId()] ?? null)?->isIncluded())) {
                    $newAlertGroupId = self::getNewExportPK("alertGroups", $item->getId());
                    $item->setId($newAlertGroupId);
                    foreach ($item->getNotificationGroups() as $notificationGroup) {
                        $notificationGroup->setId(self::remapExportObjectPK("notificationGroups", $notificationGroup->getId()));
                    }
                    return true;
                }
            });
    }

    /**
     * Analyse import objects
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @param mixed $allProjectExportConfig
     *
     * @return void
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        // Handle alert groups.
        $alertGroups = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->alertService->listAlertGroups("", PHP_INT_MAX, 0, $projectKey, $accountId));
        $alertGroupResources = [];
        foreach ($exportObjects ?? [] as $alertGroup) {
            if ($alertGroups[$alertGroup->getTitle()] ?? null) {
                $importStatus = (($objectExportConfig[$alertGroup->getId()] ?? null)?->isUpdate()) ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Ignore;
            } else {
                $importStatus = ProjectImportResourceStatus::Create;
            }

            $alertGroupResources[] = new ProjectImportResource($alertGroup->getId(), $alertGroup->getTitle(), $importStatus,
                ($alertGroups[$alertGroup->getTitle()] ?? null)?->getId());
        }

        return $alertGroupResources;
    }


    /**
     * Import objects for alert groups
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        // Analyse the import to determine rules
        $importAnalysis = $this->analyseImportObjects($accountId, $projectKey, $exportObjects, $objectExportConfig, null);

        // Handle alert groups
        $importItems = ObjectArrayUtils::indexArrayOfObjectsByMember("id", $exportObjects);

        // Loop through all import analysis
        foreach ($importAnalysis as $itemAnalysis) {

            $importItem = $importItems[$itemAnalysis->getIdentifier()];

            switch ($itemAnalysis->getImportStatus()) {

                case ProjectImportResourceStatus::Create:
                    $targetItem = $importItem;
                    $importId = $targetItem->getId();
                    $targetItem->setId(null);
                    break;
                case ProjectImportResourceStatus::Update:
                    $targetItem = $this->alertService->getAlertGroup($itemAnalysis->getExistingProjectIdentifier());
                    $targetItem->setNotificationTitle($importItem->getNotificationTitle());
                    $targetItem->setNotificationPrefixText($importItem->getNotificationPrefixText());
                    $targetItem->setNotificationSuffixText($importItem->getNotificationSuffixText());
                    $targetItem->setNotificationLevel($importItem->getNotificationLevel());
                    break;
                case ProjectImportResourceStatus::Ignore:
                    continue 2;
            }

            // Update notification groups
            foreach ($importItem->getNotificationGroups() as $notificationGroup) {
                $notificationGroup->setId(self::remapImportedItemId("notificationGroups", $notificationGroup->getId()));
            }
            $targetItem->setNotificationGroups($importItem->getNotificationGroups());



            // Save the alert group
            $alertId = $this->alertService->saveAlertGroup($targetItem, $projectKey, $accountId);

            // If creating update the import itme map
            if ($itemAnalysis->getImportStatus() == ProjectImportResourceStatus::Create)
                $this->setImportItemIdMapping("alertGroups", $importId, $alertId);

        }


    }


}