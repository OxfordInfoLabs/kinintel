<?php

namespace Kinintel\Services\ImportExport;

use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\ImportExport\DefaultProjectExporter;
use Kiniauth\ValueObjects\ImportExport\ExportableProjectResources;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kinikit\Core\Logging\Logger;
use Kinintel\Services\Alert\AlertService;
use Kinintel\ValueObjects\ImportExport\ProjectExport;
use Kinintel\ValueObjects\ImportExport\ProjectExportConfig;

class KinintelProjectExporter extends DefaultProjectExporter {

    // Override the export class for exporting
    public const EXPORT_CLASS = ProjectExport::class;
    public const EXPORT_CONFIG_CLASS = ProjectExportConfig::class;

    /**
     * Construct with all required services for export
     *
     * @param NotificationService $notificationService
     * @param AlertService $alertService
     */
    public function __construct(private NotificationService $notificationService,
                                private AlertService        $alertService) {
        parent::__construct($this->notificationService);
    }

    /**
     * Get exportable project resources
     *
     * @param int $accountId
     * @param string $projectKey
     * @return ExportableProjectResources
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        $exportableResources = parent::getExportableProjectResources($accountId, $projectKey);

        // List alert groups
        $exportableResources->addResourcesForType("alertGroups", array_map(function ($group) {
            return new ProjectExportResource($group->getId(), $group->getTitle());
        }, $this->alertService->listAlertGroups("", PHP_INT_MAX, 0, $projectKey, $accountId)));


        return $exportableResources;
    }

    /**
     * @param int $accountId
     * @param string $projectKey
     * @param \Kiniauth\ValueObjects\ImportExport\ProjectExportConfig $exportProjectConfig
     *
     * @return ProjectExport
     */
    public function exportProject(int $accountId, string $projectKey, $exportProjectConfig) {
        $parentExport = parent::exportProject($accountId, $projectKey, $exportProjectConfig);

        $alertGroupIds = $exportProjectConfig->getIncludedAlertGroupIdUpdateIndicators();
        $remappedAlertGroupIds = [];
        // Grab all notification groups
        $includedAlertGroups = array_filter($this->alertService->listAlertGroups("", PHP_INT_MAX, 0, $projectKey, $accountId),
            function ($item) use ($alertGroupIds, &$remappedAlertGroupIds) {
                if (in_array($item->getId(), array_keys($alertGroupIds))) {
                    $newAlertGroupId = $this->getNewPK("alertGroups", $item->getId());
                    $remappedAlertGroupIds[$newAlertGroupId] = $alertGroupIds[$item->getId()];
                    $item->setId($newAlertGroupId);
                    foreach ($item->getNotificationGroups() as $notificationGroup) {
                        $notificationGroup->setId($this->remapObjectPK("notificationGroups", $notificationGroup->getId()));
                    }
                    return true;
                }
            });


        return new ProjectExport(new ProjectExportConfig([], $remappedAlertGroupIds), $parentExport->getNotificationGroups(), $includedAlertGroups);

    }


}