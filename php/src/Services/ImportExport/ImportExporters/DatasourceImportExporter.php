<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\ImportExport\ExportConfig\DatasourceExportConfig;
use Kinintel\ValueObjects\ImportExport\ExportObjects\ExportedDatasource;

class DatasourceImportExporter extends ImportExporter {


    /**
     * Construct with injection services
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct(private DatasourceService $datasourceService, private DataProcessorService $dataProcessorService) {
    }


    /**
     * get collection identifier
     *
     * @return string
     */
    public function getObjectTypeCollectionIdentifier() {
        return "datasources";
    }

    /**
     * Get collection title
     *
     * @return void
     */
    public function getObjectTypeCollectionTitle() {
        return "Datasources";
    }

    public function getObjectTypeImportClassName() {
        return ExportedDatasource::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return DatasourceExportConfig::class;
    }

    /**
     * Get the exportable project resources
     *
     * @param int $accountId
     * @param string $projectKey
     *
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        return array_map(function ($datasource) {
            return new ProjectExportResource($datasource->getKey(), $datasource->getTitle(), new DatasourceExportConfig(true, false));
        }, $this->datasourceService->filterDatasourceInstances("", PHP_INT_MAX, 0, ["custom", "document", "sqldatabase"], $projectKey, $accountId));
    }

    /**
     * Create export objects using supplied config
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     * @return void
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {

        /**
         * Loop through each passed export item.
         *
         * @var DatasourceExportConfig $config
         */
        $exportObjects = [];
        foreach ($objectExportConfig as $key => $config) {
            if ($config->isIncluded()) {
                $datasource = $this->datasourceService->getDataSourceInstanceByKey($key);

                // Nullify table names in config
                $datasourceConfig = $datasource->getConfig();
                if ($datasourceConfig["tableName"] ?? null) $datasourceConfig["tableName"] = null;

                if ($config->isIncludeData()) {
                    $dataSet = $this->datasourceService->getEvaluatedDataSourceByInstanceKey($key);
                    $data = $dataSet->getAllData();
                } else {
                    $data = [];
                }


                $exportObjects[] = new ExportedDatasource(self::getNewExportPK("datasources", $key),
                    $datasource->getTitle(), $datasource->getType(), $datasource->getDescription(), $datasource->getImportKey(),
                    $datasourceConfig, $data);
            }
        }

        // Now grab all processor based data sources
        $processorBasedDataSources = $this->datasourceService->filterDatasourceInstances("", PHP_INT_MAX, 0, ["snapshot", "querycache", "caching"], $projectKey, $accountId) ?? [];

        // Loop through processor based data sources and merge these into exported objects
        foreach ($processorBasedDataSources as $dataSource) {

            // Strip processor key and suffix
            preg_match("/(.*?)(_.*[0-9]+)(.*)$/", $dataSource->getKey(), $matches);
            if (sizeof($matches) == 4) {

                $processorKey = $matches[1] . $matches[2];
                $processorConfig = $allProjectExportConfig["dataProcessors"][$processorKey] ?? null;
                if ($processorConfig?->isIncluded()) {

                    $newProcessorKey = self::getNewExportPK("dataProcessors", $processorKey);

                    $processorPrefix = $matches[1];
                    $processerSuffix = $matches[3];

                    // Grab the processor
                    $processorTitle = $this->dataProcessorService->getDataProcessorInstance($processorKey)?->getTitle();

                    // Grab the datasource
                    $datasource = $this->datasourceService->getDataSourceInstanceByKey($dataSource->getKey());

                    // Nullify table names in config
                    $datasourceConfig = $datasource->getConfig();
                    if ($datasourceConfig["tableName"] ?? null) $datasourceConfig["tableName"] = null;
                    if ($datasourceConfig["cacheDatasourceKey"] ?? null)
                        $datasourceConfig["cacheDatasourceKey"] = self::remapExportObjectPK("datasources", $datasourceConfig["cacheDatasourceKey"] ?? null);

                    $exportObjects[] = new ExportedDatasource(self::getNewExportPK("datasources", $dataSource->getKey()),
                        $datasource->getTitle(), $datasource->getType(), $datasource->getDescription(), null,
                        $datasourceConfig, [], $newProcessorKey, $processorTitle, $processorPrefix, $processerSuffix);

                }

            }


        }


        return $exportObjects;

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
     * @return ProjectImportResource[]
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $importObjects = [];

        /**
         * Loop through each export object
         */
        foreach ($exportObjects as $exportObject) {

            $configObject = $objectExportConfig[$exportObject->getKey()] ?? null;

            if ($configObject?->isIncluded()) {

                $mode = ProjectImportResourceStatus::Create;
                $existingItemIdentifier = null;
                try {
                    $existingItem = $this->datasourceService->getDatasourceInstanceByTitle($exportObject->getTitle(), $projectKey, $accountId);
                    $mode = ProjectImportResourceStatus::Update;
                    $existingItemIdentifier = $existingItem->getKey();
                } catch (ObjectNotFoundException $e) {
                }

                $importObjects[] = new ProjectImportResource($exportObject->getKey(), $exportObject->getTitle(), $mode, $existingItemIdentifier);
            }

        }

        return $importObjects;

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

        // Get the analysis for the import objects
        $analysis = ObjectArrayUtils::indexArrayOfObjectsByMember("identifier", $this->analyseImportObjects($accountId, $projectKey, $exportObjects, $objectExportConfig, null));

        // Get all data processors by title
        $dataProcessorsByTitle = ObjectArrayUtils::indexArrayOfObjectsByMember("title", $this->dataProcessorService->filterDataProcessorInstances([], $projectKey, 0, PHP_INT_MAX, $accountId) ?? []);

        /**
         * @var ExportedDatasource $exportObject
         */
        foreach ($exportObjects as $exportObject) {

            $analysisObject = $analysis[$exportObject->getKey()] ?? null;
            $exportConfig = $objectExportConfig[$exportObject->getKey()] ?? null;

            if ($analysisObject) {

                $keyPrefix = null;
                $tablePrefix = null;
                $credentialsKey = null;
                $datasourceConfig = $exportObject->getConfig() ?? [];
                switch ($exportObject->getType()) {
                    case "custom":
                        $keyPrefix = "custom_data_set_$accountId" . "_";
                        $tablePrefix = Configuration::readParameter("custom.datasource.table.prefix");
                        $credentialsKey = Configuration::readParameter("custom.datasource.credentials.key");
                        break;
                    case "document":
                        $keyPrefix = "document_data_set_$accountId" . "_";
                        $tablePrefix = Configuration::readParameter("custom.datasource.table.prefix");
                        $credentialsKey = Configuration::readParameter("custom.datasource.credentials.key");
                        break;
                }

                $datasourceKey = null;
                switch ($analysisObject->getImportStatus()) {
                    case ProjectImportResourceStatus::Create:
                        $datasourceKey = $keyPrefix . date("U");
                        break;
                    case ProjectImportResourceStatus::Update:
                        $datasourceKey = $analysisObject->getExistingProjectIdentifier();
                        break;
                }

                $datasourceConfig["tableName"] = $tablePrefix . $datasourceKey;


            } else if ($exportObject->getAssociatedDataProcessorKey()) {


                // If data processor already exists in this project with the supplied title, use the
                // key to create the key
                if (isset($dataProcessorsByTitle[$exportObject->getDataProcessorTitle()])) {
                    $datasourceKey = $dataProcessorsByTitle[$exportObject->getDataProcessorTitle()]->getKey() . $exportObject->getDataProcessorKeySuffix();
                } else {
                    $dataProcessorKey = self::remapImportedItemId("dataProcessors", $exportObject->getAssociatedDataProcessorKey());
                    if ($dataProcessorKey == $exportObject->getAssociatedDataProcessorKey()) {
                        $dataProcessorKey = $exportObject->getDataProcessorKeyPrefix() . "_" . $accountId . "_" . date("U");
                        self::setImportItemIdMapping("dataProcessors", $exportObject->getAssociatedDataProcessorKey(), $dataProcessorKey);
                    }
                    $datasourceKey = $dataProcessorKey . $exportObject->getDataProcessorKeySuffix();
                }


                $tablePrefix = null;
                $credentialsKey = null;
                $datasourceConfig = $exportObject->getConfig() ?? [];
                switch ($exportObject->getType()) {
                    case "snapshot":
                        $tablePrefix = Configuration::readParameter("snapshot.datasource.table.prefix");
                        $credentialsKey = Configuration::readParameter("snapshot.datasource.credentials.key");
                        $datasourceConfig["tableName"] = $tablePrefix . $datasourceKey;
                        break;
                    case "querycache":
                        $tablePrefix = Configuration::readParameter("querycache.datasource.table.prefix");
                        $credentialsKey = Configuration::readParameter("querycache.datasource.credentials.key");
                        $datasourceConfig["tableName"] = $tablePrefix . $datasourceKey;
                        break;
                    case "caching":
                        $datasourceConfig["cacheDatasourceKey"] = self::remapImportedItemId("datasources", $datasourceConfig["cacheDatasourceKey"] ?? null);
                        break;
                }

            } else {
                continue;
            }

            // Create and save the ds instance
            $newDatasource = new DatasourceInstance($datasourceKey, $exportObject->getTitle(), $exportObject->getType(), $datasourceConfig, $credentialsKey, projectKey: $projectKey, accountId: $accountId);
            $this->datasourceService->saveDataSourceInstance($newDatasource);

            // Update import item id mapping for downstream use.
            self::setImportItemIdMapping("datasources", $exportObject->getKey(), $datasourceKey);

            // If updating data, do this now.
            if ($exportConfig && $exportConfig->isIncludeData()) {
                $this->datasourceService->updateDatasourceInstanceByKey($datasourceKey,
                    new DatasourceUpdate($exportObject->getData()));
            }


        }

    }
}