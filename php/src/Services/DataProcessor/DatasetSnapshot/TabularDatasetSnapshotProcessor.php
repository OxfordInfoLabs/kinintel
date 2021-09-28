<?php


namespace Kinintel\Services\DataProcessor\DatasetSnapshot;


use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;
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
     * TabularDatasetSnapshotProcessor constructor.
     *
     * @param DatasetService $datasetService
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasetService, $datasourceService) {
        $this->datasetService = $datasetService;
        $this->datasourceService = $datasourceService;
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
     * @param TabularDatasetSnapshotProcessorConfiguration $config
     */
    public function process($config = null) {

        // Read the source dataset instance
        $sourceDataSetInstance = $this->datasetService->getFullDataSetInstance($config->getDatasetInstanceId());

        // Do a check to see if the target datasource exists
        try {
            $dataSourceInstance = $this->datasourceService->getDataSourceInstanceByKey($config->getSnapshotIdentifier());
        } catch (ObjectNotFoundException $e) {

            // Create a new data source instance and save it.
            $dataSourceInstance = new DatasourceInstance($config->getSnapshotIdentifier(), $config->getSnapshotIdentifier(), "sqldatabase",
                [
                    "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                    "tableName" => $config->getSnapshotIdentifier()
                ], "dataset_snapshot");
            $dataSourceInstance->setAccountId($sourceDataSetInstance->getAccountId());
            $dataSourceInstance->setProjectKey($sourceDataSetInstance->getProjectKey());


            // Save the datasource instance and return a new one
            $this->datasourceService->saveDataSourceInstance($dataSourceInstance);
        }


    }
}