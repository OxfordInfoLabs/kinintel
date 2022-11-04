<?php


namespace Kinintel\Services\DataProcessor\DatasetSnapshot;


use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\TableMapper\Mapper\TableMapper;
use Kinikit\Persistence\TableMapper\Mapper\TableMapping;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;

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


    // Data limit
    const DATA_LIMIT = 50000;


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

        $config = $instance->returnConfig();

        // Read the source dataset instance
        $sourceDataSetInstance = $this->datasetService->getFullDataSetInstance($config->getDatasetInstanceId());

        // Get the datasource instance, creating if necessary
        $dataSourceInstance = $this->getDatasourceInstance($config->getSnapshotIdentifier(), $sourceDataSetInstance->getAccountId(), $sourceDataSetInstance->getProjectKey());

        // Grab the target data source
        $dataSource = $dataSourceInstance->returnDataSource();


        // Get database connection
        $databaseConnection = $dataSource->getAuthenticationCredentials()->returnDatabaseConnection();

        // Evaluate time lapse data
        $columnTimeLapses = [];
        $distinctTimeLapses = [];
        foreach ($config->getTimeLapsedFields() ?? [] as $timeLapsedFieldSet) {
            foreach ($timeLapsedFieldSet->getFieldNames() as $fieldName) {
                $columnTimeLapses[$fieldName] = array_merge($columnTimeLapses[$fieldName] ?? [], $timeLapsedFieldSet->getDayOffsets());
            }
            foreach ($timeLapsedFieldSet->getDayOffsets() as $dayOffset) {
                $distinctTimeLapses[$dayOffset] = (new \DateTime())->sub(new \DateInterval("P" . $dayOffset . "D"))->format("Y-m-d");
            }
        }


        // Now process the Dataset progressively in blocks of 10 to control processing rate.
        $offset = 0;
        $now = date("Y-m-d");
        do {
            $dataset = $this->datasetService->getEvaluatedDataSetForDataSetInstance($sourceDataSetInstance, [], [], $offset, self::DATA_LIMIT);

            // If first time round, update the table structure
            if ($offset == 0 && $dataset->getColumns()) {
                $columns = Field::toPlainFields($dataset->getColumns());
                $fields = $this->updateDatasourceTableStructure($columns, $config->getKeyFieldNames(), $columnTimeLapses, $dataSourceInstance, $dataSource);
            }

            // Grab all data
            $sourceData = $dataset->getAllData();

            // Generate timelapse data
            $writeData = $this->generateUpdateData($sourceData, $now, $config->getSnapshotIdentifier(), $config->getKeyFieldNames(), $columnTimeLapses, $distinctTimeLapses, $databaseConnection);

            // Create an update data set and update the data source.
            $updateDataSet = new ArrayTabularDataset($fields, $writeData);
            $dataSource->update($updateDataSet);


            $offset += self::DATA_LIMIT;
        } while (sizeof($sourceData) == self::DATA_LIMIT);


    }

    /**
     * @param TabularDatasetSnapshotProcessorConfiguration $config
     * @param $sourceDataSetInstance
     * @return DatasourceInstance
     */
    private function getDatasourceInstance($instanceKey, $accountId, $projectKey) {

        $credentialsKey = Configuration::readParameter("snapshot.datasource.credentials.key");
        $tablePrefix = Configuration::readParameter("snapshot.datasource.table.prefix");

        // Do a check to see if the target datasource exists
        try {
            $dataSourceInstance = $this->datasourceService->getDataSourceInstanceByKey($instanceKey);
        } catch (ObjectNotFoundException $e) {

            // Create a new data source instance and save it.
            $dataSourceInstance = new DatasourceInstance($instanceKey, $instanceKey, "snapshot",
                [
                    "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                    "tableName" => $tablePrefix . $instanceKey
                ], $credentialsKey);
            $dataSourceInstance->setAccountId($accountId);
            $dataSourceInstance->setProjectKey($projectKey);

            // Save the datasource instance and return a new one
            $dataSourceInstance = $this->datasourceService->saveDataSourceInstance($dataSourceInstance);
        }
        return $dataSourceInstance;
    }


    /**
     * @param Field[] $columns
     * @param string[] $keyFieldNames
     * @param int[] $columnTimeLapses
     * @param DatasourceInstance $dataSourceInstance
     * @param Datasource $dataSource
     * @return Field[]
     */
    private function updateDatasourceTableStructure($columns, $keyFieldNames, $columnTimeLapses, $dataSourceInstance, $dataSource) {

        // Create fields array
        $fields = [new Field("snapshot_date", "Snapshot Date", null, Field::TYPE_DATE, true)];

        // Add each column and any timelapse variations required
        foreach ($columns as $column) {

            // Set as key field if in key field names array
            if (in_array($column->getName(), $keyFieldNames)) $column->setKeyField(true);
            $fields[] = $column;

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
    private function generateUpdateData($sourceData, $snapshotDate, $tableName, $keyFieldNames, $columnTimeLapses, $distinctTimeLapses, $databaseConnection) {


        // Do an initial parse to set up core data including snapshot date
        $updateData = [];
        $pks = [];
        foreach ($sourceData as $sourceItem) {
            $updateDataItem = $sourceItem;
            $updateDataItem["snapshot_date"] = $snapshotDate;

            if ($keyFieldNames) {
                foreach ($distinctTimeLapses as $distinctTimeLapse) {
                    $pk = [$distinctTimeLapse];
                    foreach ($keyFieldNames as $keyFieldName) {
                        $pk[] = $updateDataItem[$keyFieldName] ?? null;
                    }
                    $pks[] = $pk;
                }

            }

            $updateData[] = $updateDataItem;
        }

        // If we have some Pks to resolve, look these up now.
        if (sizeof($pks)) {

            $primaryKeyFields = array_merge(["snapshot_date"], $keyFieldNames ?? []);
            $tableMapping = new TableMapping($tableName, [], $databaseConnection, $primaryKeyFields);

            $previousEntries = $this->tableMapper->multiFetch($tableMapping, $pks, true);

            /**
             * Loop through the update data one more time and add in the extra column data
             */
            foreach ($updateData as $index => $updateDatum) {

                // Create end of PK for lookups
                $pkString = "";
                foreach ($keyFieldNames as $keyFieldName) {
                    $pkString .= "||" . ($updateDatum[$keyFieldName] ?? "");
                }

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


}