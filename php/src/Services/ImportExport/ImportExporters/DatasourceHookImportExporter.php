<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\Hook\DatasourceHookService;

class DatasourceHookImportExporter extends ImportExporter {

    /**
     * Inject dataset service
     *
     * @param DatasourceHookService $hookService
     */
    public function __construct(public DatasourceHookService $hookService) {
    }

    public function getObjectTypeCollectionIdentifier(): string {
        return "hooks";
    }

    public function getObjectTypeCollectionTitle(): string {
        return "Hooks";
    }

    public function getObjectTypeImportClassName(): string {
        return DatasourceHookInstance::class;
    }

    public function getObjectTypeExportConfigClassName(): string {
        return ObjectInclusionExportConfig::class;
    }

    public function getExportableProjectResources(int $accountId, string $projectKey): array {
        return array_map(function ($item) {
            return new ProjectExportResource($item->getId(), $item->getTitle(), new ObjectInclusionExportConfig(true));
        }, $this->hookService->filterDatasourceHookInstances($projectKey, 0, PHP_INT_MAX, $accountId));
    }

    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig): array {

        // Loop through objects
        $exportObjects = [];
        foreach ($objectExportConfig as $id => $config) {
            if ($config->isIncluded()) {
                $hook = $this->hookService->getDatasourceHookById($id);
                $hook->setId(self::getNewExportPK("hooks", $hook->getId()));
                $exportObjects[] = $hook;
            }
        }


        // Do a second pass to map datasource keys and data processor keys if required
        foreach ($exportObjects as $exportObject) {
            $exportObject->setDatasourceInstanceKey(self::remapExportObjectPK("datasources", $exportObject->getDatasourceInstanceKey()));
            $exportObject->setDataProcessorInstanceKey(self::remapExportObjectPK("dataProcessors", $exportObject->getDataProcessorInstanceKey()));
        }

        return $exportObjects;
    }

    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig): array {

        $hooksByTitle = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->hookService->filterDatasourceHookInstances($projectKey, 0, PHP_INT_MAX, $accountId));

        $importObjects = [];

        foreach ($exportObjects as $exportObject) {
            if ($objectExportConfig[$exportObject->getId()]?->isIncluded()) {
                $existingAccountObject = $hooksByTitle[$exportObject->getTitle()] ?? null;
                $importObjects[] = new ProjectImportResource($exportObject->getId(), $exportObject->getTitle(),
                    $existingAccountObject ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Create,
                    $existingAccountObject?->getId());
            }
        }

        return $importObjects;

    }

    /**
     * @param int $accountId
     * @param string $projectKey
     * @param DatasourceHookInstance[] $exportObjects
     * @param mixed $objectExportConfig
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig): void {

        $hooksByTitle = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->hookService->filterDatasourceHookInstances($projectKey, 0, PHP_INT_MAX, $accountId));

        // Loop through export objects
        foreach ($exportObjects as $exportObject) {
            if ($objectExportConfig[$exportObject->getId()]?->isIncluded()) {
                $existingAccountObject = $hooksByTitle[$exportObject->getTitle()] ?? null;

                // Sort out import item id mappings
                if ($existingAccountObject) {
                    self::setImportItemIdMapping("hooks", $exportObject->getId(), $existingAccountObject->getId());
                    $exportObject->setId($existingAccountObject->getId());
                } else {
                    $exportId = $exportObject->getId();
                    $exportObject->setId(null);
                    $newId = $this->hookService->saveHookInstance($exportObject, $projectKey, $accountId);
                    $exportObject->setId($newId);
                    self::setImportItemIdMapping("hooks", $exportId, $newId);
                }
            }
        }

        // Second loop to sort out ids and save
        foreach ($exportObjects as $exportObject) {

            // Remap hierarchy fields
            $exportObject->setDatasourceInstanceKey(self::remapImportedItemId("datasources", $exportObject->getDatasourceInstanceKey()));
            $exportObject->setDataProcessorInstanceKey(self::remapImportedItemId("dataProcessors", $exportObject->getDataProcessorInstanceKey()));

            // Save the dataset instance
            $this->hookService->saveHookInstance($exportObject, $projectKey, $accountId);

        }
    }
}