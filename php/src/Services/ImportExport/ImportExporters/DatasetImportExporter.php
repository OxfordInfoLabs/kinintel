<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetService;
use Monolog\Logger;

class DatasetImportExporter extends ImportExporter {

    /**
     * Inject dataset service
     *
     * @param DatasetService $datasetService
     */
    public function __construct(public DatasetService $datasetService) {
    }

    public function getObjectTypeCollectionIdentifier() {
        return "datasets";
    }

    public function getObjectTypeCollectionTitle() {
        return "Queries";
    }

    public function getObjectTypeImportClassName() {
        return DatasetInstanceSummary::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return ObjectInclusionExportConfig::class;
    }

    /**
     * Return exportable project resources
     *
     * @param int $accountId
     * @param string $projectKey
     *
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        return array_map(function ($item) {
            return new ProjectExportResource($item->getId(), $item->getTitle(), new ObjectInclusionExportConfig(true));
        }, $this->datasetService->filterDataSetInstances("", [], [], $projectKey, 0, PHP_INT_MAX, $accountId));
    }

    /**
     * Create export objects
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     *
     * @return DatasetInstanceSummary[]
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {

        // Loop through objects
        $exportObjects = [];
        foreach ($objectExportConfig as $key => $config) {
            if ($config->isIncluded()) {
                $dataset = $this->datasetService->getDataSetInstance($key);
                $dataset->setId(self::getNewExportPK("datasets", $dataset->getId()));
                $exportObjects[] = $dataset;
            }
        }

        // Now do a second pass of export objects to remap dependencies
        foreach ($exportObjects as $exportObject) {
            $exportObject->setDatasourceInstanceKey(self::remapExportObjectPK("datasources", $exportObject->getDatasourceInstanceKey()));
            $exportObject->setDatasetInstanceId(self::remapExportObjectPK("datasets", $exportObject->getDatasetInstanceId()));

            foreach ($exportObject->getTransformationInstances() ?? [] as $transformationInstance) {
                $config = $transformationInstance->getConfig();
                switch ($transformationInstance->getType()) {
                    case "join":
                        $config->setJoinedDataSourceInstanceKey(self::remapExportObjectPK("datasources", $config->getJoinedDataSourceInstanceKey()));
                        $config->setJoinedDataSetInstanceId(self::remapExportObjectPK("datasets", $config->getJoinedDataSetInstanceId()));
                        break;
                    case "combine":
                        $config->setCombinedDataSourceInstanceKey(self::remapExportObjectPK("datasources", $config->getCombinedDataSourceInstanceKey()));
                        $config->setCombinedDataSetInstanceId(self::remapExportObjectPK("datasets", $config->getCombinedDataSetInstanceId()));
                        break;
                }
                $transformationInstance->setConfig($config);
            }

        }


        return $exportObjects;
    }

    /**
     * Analyse the import objects for datasets
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @param mixed $allProjectExportConfig
     *
     * @return ProjectImportResource
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $accountQueriesByTitle = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->datasetService->filterDataSetInstances("", [], [], $projectKey, 0, PHP_INT_MAX, $accountId));

        $importObjects = [];
        foreach ($exportObjects as $exportObject) {
            if ($objectExportConfig[$exportObject->getId()]?->isIncluded()) {
                $existingAccountObject = $accountQueriesByTitle[$exportObject->getTitle()] ?? null;
                $importObjects[] = new ProjectImportResource($exportObject->getId(), $exportObject->getTitle(),
                    $existingAccountObject ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Create,
                    $existingAccountObject?->getId());
            }
        }

        return $importObjects;

    }

    /**
     * Import objects into account
     *
     * @param int $accountId
     * @param string $projectKey
     * @param DatasetInstanceSummary[] $exportObjects
     * @param mixed $objectExportConfig
     *
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $accountQueriesByTitle = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->datasetService->filterDataSetInstances("", [], [], $projectKey, 0, PHP_INT_MAX, $accountId));

        // Loop through export objects
        foreach ($exportObjects as $exportObject) {
            if ($objectExportConfig[$exportObject->getId()]?->isIncluded()) {
                $existingAccountObject = $accountQueriesByTitle[$exportObject->getTitle()] ?? null;

                // Sort out import item id mappings
                if ($existingAccountObject) {
                    self::setImportItemIdMapping("datasets", $exportObject->getId(), $existingAccountObject->getId());
                    $exportObject->setId($existingAccountObject->getId());
                } else {
                    $exportId = $exportObject->getId();
                    $exportObject->setId(null);
                    $newId = $this->datasetService->saveDataSetInstance($exportObject, $projectKey, $accountId);
                    $exportObject->setId($newId);
                    self::setImportItemIdMapping("datasets", $exportId, $newId);
                }
            }
        }

        // Second loop to sort out ids and save
        foreach ($exportObjects as $exportObject) {

            // Remap hierarchy fields
            $exportObject->setDatasourceInstanceKey(self::remapImportedItemId("datasources", $exportObject->getDatasourceInstanceKey()));
            $exportObject->setDatasetInstanceId(self::remapImportedItemId("datasets", $exportObject->getDatasetInstanceId()));

            // Remap transformation fields
            foreach ($exportObject->getTransformationInstances() ?? [] as $transformationInstance) {
                $config = $transformationInstance->getConfig();
                switch ($transformationInstance->getType()) {
                    case "join":
                        $config->setJoinedDataSourceInstanceKey(self::remapImportedItemId("datasources", $config->getJoinedDataSourceInstanceKey()));
                        $config->setJoinedDataSetInstanceId(self::remapImportedItemId("datasets", $config->getJoinedDataSetInstanceId()));
                        break;
                    case "combine":
                        $config->setCombinedDataSourceInstanceKey(self::remapImportedItemId("datasources", $config->getCombinedDataSourceInstanceKey()));
                        $config->setCombinedDataSetInstanceId(self::remapImportedItemId("datasets", $config->getCombinedDataSetInstanceId()));
                        break;
                }
            }

            // Save the dataset instance
            $this->datasetService->saveDataSetInstance($exportObject, $projectKey, $accountId);

        }


    }
}