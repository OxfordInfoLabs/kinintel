<?php

namespace Kinintel\Services\ImportExport;

use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\ImportExport\DefaultProjectImporter;
use Kiniauth\ValueObjects\ImportExport\ProjectExport;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Util\ArrayUtils;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Services\Alert\AlertService;

class KinintelProjectImporter extends DefaultProjectImporter {


    public function __construct(NotificationService $notificationService, private AlertService $alertService) {
        parent::__construct($notificationService);
    }

    /**
     * Analyse import
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     * @return \Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis
     */
    public function analyseImport(int $accountId, string $projectKey, ProjectExport $projectExport) {

        $importAnalysis = parent::analyseImport($accountId, $projectKey, $projectExport);


        // Handle alert groups.
        $alertGroups = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->alertService->listAlertGroups("", PHP_INT_MAX, 0, $projectKey, $accountId));
        $alertGroupUpdateIndicators = $projectExport->getExportConfig()->getIncludedAlertGroupIdUpdateIndicators();
        $alertGroupResources = [];
        foreach ($projectExport->getAlertGroups() ?? [] as $alertGroup) {
            if ($alertGroups[$alertGroup->getTitle()] ?? null) {
                $importStatus = $alertGroupUpdateIndicators[$alertGroup->getId()] ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Ignore;
            } else {
                $importStatus = ProjectImportResourceStatus::Create;
            }

            $alertGroupResources[] = new ProjectImportResource($alertGroup->getId(), $alertGroup->getTitle(), $importStatus);
        }
        $importAnalysis->addResourcesForType("Alert Groups", $alertGroupResources);

        return $importAnalysis;
    }


    /**
     * Import a project using an export object
     *
     * @param int $accountId
     * @param string $projectKey
     * @param ProjectExport $projectExport
     *
     * @return void
     */
    public function importProject(int $accountId, string $projectKey, ProjectExport $projectExport) {

        // Process parent imports first for dependencies.
        parent::importProject($accountId, $projectKey, $projectExport);

        // Grab the export config
        $exportConfig = $projectExport->getExportConfig();

        // Analyse the import to determine rules
        $importAnalysis = $this->analyseImport($accountId, $projectKey, $projectExport);

        // Handle alert groups
        $alertGroupsForImport = ObjectArrayUtils::indexArrayOfObjectsByMember("id",$projectExport->getAlertGroups());


    }


}