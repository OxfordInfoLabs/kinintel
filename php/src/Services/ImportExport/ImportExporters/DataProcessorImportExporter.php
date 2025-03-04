<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\ValueObjects\DataProcessor\DataProcessorItem;

class DataProcessorImportExporter extends ImportExporter {

    /**
     * @param DataProcessorService $dataProcessorService
     */
    public function __construct(public DataProcessorService $dataProcessorService) {
    }


    public function getObjectTypeCollectionIdentifier() {
        return "dataProcessors";
    }

    public function getObjectTypeCollectionTitle() {
        return "Data Processors";
    }

    public function getObjectTypeImportClassName() {
        return DataProcessorItem::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return ObjectInclusionExportConfig::class;
    }

    /**
     * Get a list of exportable project resources
     *
     * @param int $accountId
     * @param string $projectKey
     *
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        return array_map(function ($resource) {
            $category = str_contains($resource->getType(), "snapshot") ? "snapshot" : $resource->getType();
            return new ProjectExportResource($resource->getKey(), $resource->getTitle(), new ObjectInclusionExportConfig(true), $category);
        }, $this->dataProcessorService->filterDataProcessorInstances([], $projectKey, 0, PHP_INT_MAX, $accountId));
    }

    /**
     * Create export objects
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     *
     * @return DataProcessorItem[]
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {

        $exportObjects = [];
        foreach ($objectExportConfig as $key => $config) {
            if ($config->isIncluded()) {
                $processor = $this->dataProcessorService->getDataProcessorInstance($key);
                $processor->setKey(self::getNewExportPK("dataProcessors", $key));
                if ($processor->getRelatedObjectType() == "DatasetInstance")
                    $processor->setRelatedObjectKey(self::remapExportObjectPK("datasets", $processor->getRelatedObjectKey()));

                // Remap config if required
                $config = $processor->getConfig();
                switch ($processor->getType()) {
                    case "querycaching":
                        $config["sourceQueryId"] = self::remapExportObjectPK("datasets", $config["sourceQueryId"] ?? null);
                        break;
                }
                $processor->setConfig($config);

                $exportObjects[] = DataProcessorItem::fromDataProcessorInstance($processor);

            }
        }

        return $exportObjects;

    }

    /**
     * Analyse import for processors
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @param mixed $allProjectExportConfig
     *
     * @return ProjectImportResource[]
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $allAccountDatasources = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->dataProcessorService->filterDataProcessorInstances([],
            $projectKey, 0, PHP_INT_MAX, $accountId));

        /**
         * Loop through export objects, check for inclusion and decide whether this is a create or update
         */
        $importResources = [];
        foreach ($exportObjects as $exportObject) {
            $existingItem = $allAccountDatasources[$exportObject->getTitle()] ?? null;
            if ($objectExportConfig[$exportObject->getKey()]?->isIncluded()) {

                $groupingTitle = str_contains($exportObject->getType(), "snapshot") ? "Snapshots" :
                    ($exportObject->getType() == "querycaching" ? "Query Caches" : "Other");

                $importResources[] = new ProjectImportResource($exportObject->getKey(), $exportObject->getTitle(),
                    $existingItem ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Create,
                    $existingItem ? $existingItem->getKey() : null, $groupingTitle);
            }
        }

        return $importResources;

    }

    /**
     * Perform import using export objects and project config
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     *
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $allAccountDataProcessors = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->dataProcessorService->filterDataProcessorInstances([],
            $projectKey, 0, PHP_INT_MAX, $accountId));

        /**
         * @var DataProcessorItem $exportObject
         */
        foreach ($exportObjects as $exportObject) {

            $existingItem = $allAccountDataProcessors[$exportObject->getTitle()] ?? null;
            $importKey = $exportObject->getKey();

            $exportObject->setKey($existingItem?->getKey());
            $saveObject = $exportObject->toDataProcessorInstance($projectKey, $accountId);

            // Synchronise schedule status etc if existing item
            if ($existingItem) {
                if ($existingItem->getScheduledTask() && $saveObject->getScheduledTask()) {
                    $saveObject->getScheduledTask()->setStatus($existingItem->getScheduledTask()->getStatus());
                    $saveObject->getScheduledTask()->setLastStartTime($existingItem->getScheduledTask()->getLastStartTime());
                }
            }

            if ($exportObject->getRelatedObjectType() == "DatasetInstance") {
                $saveObject->setRelatedObjectType("DatasetInstance");
                $saveObject->setRelatedObjectKey(self::remapImportedItemId("datasets", $exportObject->getRelatedObjectPrimaryKey()));
            }

            $config = $exportObject->getConfig();

            if ($saveObject->getType() == "querycaching") {
                $config["sourceQueryId"] = self::remapImportedItemId("datasets", $config["sourceQueryId"] ?? null);
            }

            $saveObject->setConfig($config);

            // Create the save object and map import key for future use.
            $newKey = $this->dataProcessorService->saveDataProcessorInstance($saveObject);
            self::setImportItemIdMapping("dataProcessors", $importKey, $newKey);

            // If query caching, trigger the data processor
            if ($exportObject->getType() == "querycaching") {
                $this->dataProcessorService->triggerDataProcessorInstance($newKey);
            }

        }


    }
}