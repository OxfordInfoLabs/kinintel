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
    public function __construct(private DatasourceService $datasourceService) {
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
     * @param mixed $exportProjectConfig
     * @return void
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $exportProjectConfig) {

        /**
         * Loop through each passed export item.
         *
         * @var DatasourceExportConfig $config
         */
        $exportObjects = [];
        foreach ($exportProjectConfig as $key => $config) {
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
                    $datasource->getTitle(), $datasource->getType(), $datasource->getDescription(),
                    $datasourceConfig, $data);
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
     * @param mixed $exportProjectConfig
     *
     * @return ProjectImportResource[]
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $exportProjectConfig) {

        $importObjects = [];

        /**
         * Loop through each export object
         */
        foreach ($exportObjects as $exportObject) {

            if ($exportProjectConfig[$exportObject->getKey()]->isIncluded()) {

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
     * @param mixed $exportProjectConfig
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $exportProjectConfig) {

        // Get the analysis for the import objects
        $analysis = ObjectArrayUtils::indexArrayOfObjectsByMember("identifier", $this->analyseImportObjects($accountId, $projectKey, $exportObjects, $exportProjectConfig));

        /**
         * @var ExportedDatasource $exportObject
         */
        foreach ($exportObjects as $exportObject) {

            $analysisObject = $analysis[$exportObject->getKey()];
            $exportConfig = $exportProjectConfig[$exportObject->getKey()];

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

            // Create and save the ds instance
            $newDatasource = new DatasourceInstance($datasourceKey, $exportObject->getTitle(), $exportObject->getType(), $datasourceConfig, $credentialsKey, projectKey: $projectKey, accountId: $accountId);
            $this->datasourceService->saveDataSourceInstance($newDatasource);

            // Update import item id mapping for downstream use.
            self::setImportItemIdMapping("datasources", $exportObject->getKey(), $datasourceKey);

            // If updating data, do this now.
            if ($exportConfig->isIncludeData()) {
                $this->datasourceService->updateDatasourceInstanceByKey($datasourceKey,
                    new DatasourceUpdate($exportObject->getData()));
            }


        }

    }
}