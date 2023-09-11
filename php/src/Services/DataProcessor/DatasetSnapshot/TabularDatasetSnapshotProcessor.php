<?php


namespace Kinintel\Services\DataProcessor\DatasetSnapshot;


use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinikit\Persistence\Database\Exception\SQLException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\TableMapper\Mapper\TableMapper;
use Kinikit\Persistence\TableMapper\Mapper\TableMapping;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class TabularDatasetSnapshotProcessor implements DataProcessor {

    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     * @var TableMapper
     */
    private $tableMapper;


    /**
     * @var integer
     */
    private $idCounter = 0;

    // Data limit
    const DEFAULT_DATA_LIMIT = 50000;


    /**
     * TabularDatasetSnapshotProcessor constructor.
     *
     * @param DatasetService $datasetService
     * @param DatasourceService $datasourceService
     * @param TableMapper $tableMapper
     */
    public function __construct($datasetService, $datasourceService, $tableMapper) {
        $this->datasetService = $datasetService;
        $this->datasourceService = $datasourceService;
        $this->tableMapper = $tableMapper;
    }


    /**
     * Return the config class in use for this tabular snapshot processor
     *
     * @return string|void
     */
    public function getConfigClass() {
        return TabularDatasetSnapshotProcessorConfiguration::class;
    }


    /**
     * Process this snapshot
     *
     * @param DataProcessorInstance $instance
     */
    public function process($instance) {

        /**
         * @var TabularDatasetSnapshotProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        // Read the source dataset instance
        $sourceDataSetInstance = $this->datasetService->getFullDataSetInstance($config->getDatasetInstanceId());

        // Get the datasource instance, creating if necessary
        $instanceKey = $config->getSnapshotIdentifier();


        list($dataSourceInstance, $dataSourceInstanceLatest, $dataSourceInstancePending) = $this->getDatasourceInstances(
            $instance, $sourceDataSetInstance->getAccountId(), $sourceDataSetInstance->getProjectKey(), $config);


        // Grab the target data sources and database connections
        if ($config->isCreateHistory()) {
            $dataSource = $dataSourceInstance->returnDataSource();
            $databaseConnection = $dataSource->getAuthenticationCredentials()->returnDatabaseConnection();
        }
        if ($config->isCreateLatest()) {
            $dataSourceLatest = $dataSourceInstanceLatest->returnDataSource();
            $dataSourcePending = $dataSourceInstancePending->returnDataSource();
        }

        // Evaluate any time lapse data
        list($columnTimeLapses, $distinctTimeLapses) = $this->evaluateTimeLapseData($instanceKey, $config);

        // set read size either from config or use default
        $readChunkSize = is_numeric($config->getReadChunkSize()) ? $config->getReadChunkSize() : self::DEFAULT_DATA_LIMIT;

        // Now process the Dataset progressively in blocks of 10 to control processing rate.
        $offset = 0;
        $now = date("Y-m-d H:i:s");
        do {
            $dataset = $this->datasetService->getEvaluatedDataSetForDataSetInstance($sourceDataSetInstance, [], [], $offset, $readChunkSize);

            // If first time round, update the table structure
            if ($offset == 0 && $dataset->getColumns()) {
                $columns = Field::toPlainFields($dataset->getColumns());
                if ($config->isCreateHistory())
                    $fields = $this->updateDatasourceTableStructure($columns, $config->getKeyFieldNames(), $columnTimeLapses, $dataSourceInstance, $dataSource);
                if ($config->isCreateLatest()) {
                    $fields = $this->updateDatasourceTableStructure($columns, $config->getKeyFieldNames(), $columnTimeLapses, $dataSourceInstancePending, $dataSourcePending);
                    $this->updateDatasourceTableStructure($columns, $config->getKeyFieldNames(), $columnTimeLapses, $dataSourceInstanceLatest, $dataSourceLatest, true);
                }
            }

            // Grab all data
            $sourceData = $dataset->getAllData();


            // Generate timelapse data, create update and update data sources
            if ($config->isCreateHistory()) {
                $historyTableName = $dataSourceInstance->getConfig()["tableName"];
                $writeData = $this->generateUpdateData($sourceData, $now, $historyTableName, $config->getKeyFieldNames(), $columnTimeLapses, $distinctTimeLapses, $databaseConnection);
                $updateDataSet = new ArrayTabularDataset($fields, $writeData);
                $dataSource->update($updateDataSet, UpdatableDatasource::UPDATE_MODE_REPLACE);
            } else {
                $writeData = $this->generateUpdateData($sourceData, $now);
            }

            if ($config->isCreateLatest()) {
                $updateDataSet = new ArrayTabularDataset($fields, $writeData);
                $dataSourcePending->update($updateDataSet, UpdatableDatasource::UPDATE_MODE_REPLACE);
            }

            $offset += $readChunkSize;
        } while (sizeof($sourceData) == $readChunkSize);


        // Replace the latest
        if ($config->isCreateLatest()) {

            $dataSourceLatest->onInstanceDelete();

            $fieldsLatest = $this->updateDatasourceTableStructure($columns, $config->getKeyFieldNames(), $columnTimeLapses, $dataSourceInstanceLatest, $dataSourceLatest, true);
            $offset = 0;
            do {
                $pendingDataSet = $this->datasourceService->getEvaluatedDataSource($instanceKey . "_pending", [],
                    [new TransformationInstance("filter", new FilterTransformation([new Filter("[[snapshot_date]]", $now)]))], $offset, $readChunkSize);
                $pendingData = $pendingDataSet->getAllData();
                foreach ($pendingData as &$entry) {
                    unset($entry["snapshot_date"]);
                }

                $writeData = new ArrayTabularDataset($fieldsLatest, $pendingData);
                $dataSourceLatest->update($writeData, UpdatableDatasource::UPDATE_MODE_REPLACE);
                $offset += $readChunkSize;
            } while (sizeof($pendingData) == $readChunkSize);

        }
    }


    /**
     * @param DataProcessorInstance $dataProcessorInstance
     * @param integer $accountId
     * @param string $projectKey
     * @param TabularDatasetSnapshotProcessorConfiguration $config
     * @return DatasourceInstance[]|null[]
     * @throws \Kinikit\Core\Validation\ValidationException
     */
    private function getDatasourceInstances($dataProcessorInstance, $accountId, $projectKey, $config) {

        //TODO be refactored
        $instanceKey = $config->getSnapshotIdentifier();

        $credentialsKey = Configuration::readParameter("snapshot.datasource.credentials.key");
        $tablePrefix = Configuration::readParameter("snapshot.datasource.table.prefix");

        $instanceKeyLatest = $instanceKey . "_latest";
        $instanceKeyPending = $instanceKey . "_pending";

        $dataSourceInstance = null;
        $dataSourceInstanceLatest = null;
        $dataSourceInstancePending = null;


        // Do a check to see if the target datasource exists
        if ($config->isCreateHistory()) {
            try {
                $dataSourceInstance = $this->datasourceService->getDataSourceInstanceByKey($instanceKey);
            } catch (ObjectNotFoundException $e) {


                // Create a new data source instance and save it.
                $dataSourceInstance = new DatasourceInstance($instanceKey, $dataProcessorInstance->getTitle() . " History", "snapshot",
                    [
                        "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                        "tableName" => $tablePrefix . $instanceKey
                    ], $credentialsKey);
                $dataSourceInstance->setAccountId($accountId);
                $dataSourceInstance->setProjectKey($projectKey);
            }
        }

        // Same for the latest datasource
        if ($config->isCreateLatest()) {
            try {
                $dataSourceInstanceLatest = $this->datasourceService->getDataSourceInstanceByKey($instanceKeyLatest);
            } catch (ObjectNotFoundException $e) {

                $dataSourceInstanceLatest = new DatasourceInstance($instanceKeyLatest, $dataProcessorInstance->getTitle() . " Latest", "snapshot",
                    [
                        "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                        "tableName" => $tablePrefix . $instanceKeyLatest
                    ], $credentialsKey);
                $dataSourceInstanceLatest->setAccountId($accountId);
                $dataSourceInstanceLatest->setProjectKey($projectKey);
            }
            try {
                $dataSourceInstancePending = $this->datasourceService->getDataSourceInstanceByKey($instanceKeyPending);

                $pendingDatasource = $dataSourceInstancePending->returnDataSource();

                try {
                    $pendingDatasource->onInstanceDelete();
                } catch (SQLException $e) {
                    // OK
                }
            } catch (ObjectNotFoundException $e) {
                $dataSourceInstancePending = new DatasourceInstance($instanceKeyPending, $instanceKeyPending, "snapshot",
                    [
                        "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                        "tableName" => $tablePrefix . $instanceKeyPending
                    ], $credentialsKey);
                $dataSourceInstancePending->setAccountId($accountId);
                $dataSourceInstancePending->setProjectKey($projectKey);
            }
        }

        return [$dataSourceInstance, $dataSourceInstanceLatest, $dataSourceInstancePending];
    }


    /**
     * @param Field[] $columns
     * @param string[] $keyFieldNames
     * @param int[] $columnTimeLapses
     * @param DatasourceInstance $dataSourceInstance
     * @param Datasource $dataSource
     * @param false $latest
     * @return Field[]
     */
    private function updateDatasourceTableStructure($columns, $keyFieldNames, $columnTimeLapses, $dataSourceInstance, $dataSource, $latest = false) {

        // Create fields array
        if (!$latest) $fields = [new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE_TIME, true)];

        // Always add a snapshot item id representing the primary key
        $fields[] = new Field("snapshot_item_id", "Snapshot Item Id", null, Field::TYPE_STRING, true);


        // Add each column and any timelapse variations required
        foreach ($columns as $column) {

            // Sort out fields
            $fields[] = $column;

            // Remove any key field setting as this is replaced by the snapshot item id.
            $column->setKeyField(false);

            // Revert any ID fields to Integers
            if ($column->getType() === Field::TYPE_ID) {
                $column->setType(Field::TYPE_INTEGER);
            }

            // Add additional column time lapse columns
            if (isset($columnTimeLapses[$column->getName()])) {
                foreach ($columnTimeLapses[$column->getName()] as $columnTimeLapse) {
                    $fields[] = new Field($column->getName() . "_" . $columnTimeLapse . "_days_ago", null, null, $column->getType());
                }
            }


        }

        // Update fields and save.
        $config = $dataSource->getConfig();
        $config->setColumns($fields);
        $dataSourceInstance->setConfig($config);
        $this->datasourceService->saveDataSourceInstance($dataSourceInstance);

        return $fields;
    }

    /**
     * Generate update data from source data
     */
    private function generateUpdateData($sourceData, $snapshotDate, $tableName = null, $keyFieldNames = [], $columnTimeLapses = [], $distinctTimeLapses = [], $databaseConnection = null) {


        // Do an initial parse to set up core data including snapshot date
        $updateData = [];
        $pks = [];
        foreach ($sourceData as $sourceItem) {
            $updateDataItem = $sourceItem;
            $updateDataItem["snapshot_date"] = $snapshotDate;

            if ($keyFieldNames) {

                // Calculate a hashed snapshot item id from the concatenation of all key field values
                $snapshotItemId = "";
                foreach ($keyFieldNames as $keyFieldName) {
                    $snapshotItemId .= $updateDataItem[$keyFieldName] ?? "";
                }
                $snapshotItemId = hash("sha512", $snapshotItemId);
                $updateDataItem["snapshot_item_id"] = $snapshotItemId;

                foreach ($distinctTimeLapses as $distinctTimeLapse) {
                    $pks[] = [$distinctTimeLapse, $snapshotItemId];
                }

            } else {
                $updateDataItem["snapshot_item_id"] = $this->idCounter;
                $this->idCounter++;
            }

            $updateData[] = $updateDataItem;
        }

        // If we have some Pks to resolve, look these up now.
        if ($tableName && sizeof($pks)) {

            $primaryKeyFields = ["snapshot_date", "snapshot_item_id"];
            $tableMapping = new TableMapping($tableName, [], $databaseConnection, $primaryKeyFields);

            $previousEntries = $this->tableMapper->multiFetch($tableMapping, $pks, true);

            /**
             * Loop through the update data one more time and add in the extra column data
             */
            foreach ($updateData as $index => $updateDatum) {

                // Create end of PK for lookups
                $pkString = "||" . ($updateDatum["snapshot_item_id"] ?? "");;

                foreach ($columnTimeLapses as $columnName => $timeLapses) {
                    foreach ($timeLapses as $timeLapse) {
                        $timeLapseColumnName = $columnName . "_" . $timeLapse . "_days_ago";
                        $previousEntry = $previousEntries[$distinctTimeLapses[$timeLapse] . $pkString] ?? null;
                        $updateDatum[$timeLapseColumnName] = $previousEntry ? ($previousEntry[$columnName] ?? null) : null;
                    }
                }

                // Resync update data
                $updateData[$index] = $updateDatum;
            }
        }

        return $updateData;

    }

    /**
     * @param TabularDatasetSnapshotProcessorConfiguration $config
     * @param string
     * @return array[]
     */
    private function evaluateTimeLapseData($dataSourceInstanceKey, $config) {
        $columnTimeLapses = [];
        $distinctTimeLapses = [];
        foreach ($config->getTimeLapsedFields() ?? [] as $timeLapsedFieldSet) {
            foreach ($timeLapsedFieldSet->getFieldNames() as $fieldName) {
                $columnTimeLapses[$fieldName] = array_merge($columnTimeLapses[$fieldName] ?? [], $timeLapsedFieldSet->getDayOffsets());
            }
            foreach ($timeLapsedFieldSet->getDayOffsets() as $dayOffset) {
                $date = (new \DateTime())->sub(new \DateInterval("P" . $dayOffset . "D"))->format("Y-m-d");
                try {
                    $evaluateDatasource = $this->datasourceService->getEvaluatedDataSource($dataSourceInstanceKey, [], [new TransformationInstance("filter", new FilterTransformation([new Filter("substr([[snapshot_date]], 1, 10)", $date, "eq")]))], 0, 1);
                    if ($nextLine = $evaluateDatasource->nextRawDataItem()) {
                        $distinctTimeLapses[$dayOffset] = $nextLine["snapshot_date"];
                    } else {
                        $distinctTimeLapses[$dayOffset] = null;
                    }
                } catch (ObjectNotFoundException $e) {
                    // This is OK
                }
            }
        }


        return [$columnTimeLapses, $distinctTimeLapses];
    }


}
