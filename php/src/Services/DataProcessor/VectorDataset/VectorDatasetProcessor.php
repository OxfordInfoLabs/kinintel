<?php

namespace Kinintel\Services\DataProcessor\VectorDataset;

use Exception;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding\OpenAIEmbeddingService;
use Kinintel\ValueObjects\DataProcessor\Configuration\VectorDataset\VectorDatasetProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;

class VectorDatasetProcessor implements DataProcessor {

    /**
     * @var DatasetService
     */
    private DatasetService $datasetService;


    /**
     * @var DatasourceService
     */
    private DatasourceService $datasourceService;

    /**
     * @var OpenAIEmbeddingService
     */
    private OpenAIEmbeddingService $embeddingService;

    // Data limit
    const DEFAULT_DATA_LIMIT = 50000;


    /**
     * TabularDatasetSnapshotProcessor constructor.
     *
     * @param DatasetService $datasetService
     * @param DatasourceService $datasourceService
     * @param OpenAIEmbeddingService $embeddingService
     */
    public function __construct(DatasetService $datasetService, DatasourceService $datasourceService, OpenAIEmbeddingService $embeddingService) {
        $this->datasetService = $datasetService;
        $this->datasourceService = $datasourceService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Return the config class
     *
     * @return string
     */
    public function getConfigClass(): string {
        return VectorDatasetProcessorConfiguration::class;
    }

    /**
     * @param DataProcessorInstance $instance
     * @return void
     * @throws Exception
     */
    public function process($instance): void {

        /**
         * @var VectorDatasetProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        $datasetId = $config->getDatasetInstanceId();

        /**
         * @var DatasetInstance $sourceDataSetInstance
         */
        $sourceDataSetInstance = $this->datasetService->getFullDataSetInstance($datasetId);

        // ToDo: handle a source datasource

        $targetDatasourceInstance = $this->getTargetDatasourceInstance($instance, $sourceDataSetInstance->getAccountId(), $sourceDataSetInstance->getProjectKey(), $config);
        $targetDatasource = $targetDatasourceInstance->returnDataSource();

        $databaseConnection = $targetDatasource->getAuthenticationCredentials()->returnDatabaseConnection();

        $readChunkSize = is_numeric($config->getReadChunkSize()) ? $config->getReadChunkSize() : self::DEFAULT_DATA_LIMIT;

        $offset = 0;

        do {
            $dataset = $this->datasetService->getEvaluatedDataSetForDataSetInstance($sourceDataSetInstance, [], [], $offset, $readChunkSize);

            // If first time round, update the table structure
            if ($offset == 0 && $config->getContentColumnName()) {
                $fields = $this->updateDatasourceTableStructure($config->getIdentifierColumnNames(), $config->getContentColumnName(), $targetDatasourceInstance, $targetDatasource, $dataset);
            }

            // Grab all data
            $sourceData = $dataset->getAllData();
            $writeData = $this->generateUpdateData($sourceData, $config->getIdentifierColumnNames(), $config->getContentColumnName());

            $updateDataSet = new ArrayTabularDataset($fields, $writeData);
            $targetDatasource->update($updateDataSet, UpdatableDatasource::UPDATE_MODE_REPLACE);

            $offset += $readChunkSize;

        } while (sizeof($sourceData) == $readChunkSize);

        // Now we want to create a datasource which queries the table
//        $queryDatasourceKey = $sourceDataSetInstance->getDatasourceInstanceKey() . "_embeddings";
//        $queryDatasourceTitle = $sourceDataSetInstance->getTitle() . " Embeddings";
//        $queryDatasourceConfig = new SQLDatabaseDatasourceConfig("table", $config->getVectorDatasourceIdentifier());
//        $credentialsKey = Configuration::readParameter("vector.datasource.credentials.key");
//
//        $queryDatasourceInstance = new DatasourceInstance($queryDatasourceKey, $queryDatasourceTitle, "sqldatabase", $queryDatasourceConfig, $credentialsKey);
//        $this->datasourceService->saveDataSourceInstance($queryDatasourceInstance);

    }

    /**
     * @param DataProcessorInstance $dataProcessorInstance
     * @param int $accountId
     * @param string|null $projectKey
     * @param VectorDatasetProcessorConfiguration $config
     * @return DatasourceInstance
     */
    private function getTargetDatasourceInstance(DataProcessorInstance $dataProcessorInstance, int $accountId,
                                                 ?string               $projectKey, VectorDatasetProcessorConfiguration $config): DatasourceInstance {

        $instanceKey = $dataProcessorInstance->getKey();
        $credentialsKey = Configuration::readParameter("vector.datasource.credentials.key");

        // Get it, and create if it doesn't exist
        try {
            $dataSourceInstance = $this->datasourceService->getDataSourceInstanceByKey($instanceKey);

        } catch (ObjectNotFoundException $e) {

            // Create a new data source instance and save it.
            $dataSourceInstance = new DatasourceInstance($instanceKey, $dataProcessorInstance->getTitle(), "sqldatabase",
                [
                    "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                    "tableName" => $instanceKey
                ], $credentialsKey);
            $dataSourceInstance->setAccountId($accountId);
            $dataSourceInstance->setProjectKey($projectKey);
        }

        return $dataSourceInstance;

    }

    /**
     * @param array $identifierColumnNames
     * @param string $contentColumnName
     * @param DatasourceInstance $dataSourceInstance
     * @param Datasource $dataSource
     * @param TabularDataset $sourceDataset
     * @return Field[]
     */
    private function updateDatasourceTableStructure(array $identifierColumnNames, string $contentColumnName, DatasourceInstance $dataSourceInstance, Datasource $dataSource, TabularDataset $sourceDataset): array {

        $columnNames = array_unique(array_merge($identifierColumnNames, [$contentColumnName]));
        foreach ($columnNames as $columnName) {
            $column = $sourceDataset->getColumnByName($columnName);
            $column->setKeyField(true);
            $columns[] = $column;
        }

        $columns[] = new Field("embedding", "Embedding", null, Field::TYPE_VECTOR);

        // Update fields and save.
        $config = $dataSource->getConfig();
        $config->setColumns($columns);
        $config->setManageTableStructure(true);

        $dataSourceInstance->setConfig($config);
        $this->datasourceService->saveDataSourceInstance($dataSourceInstance);

        return $columns;

    }

    /**
     * @param array $sourceData
     * @param string[] $identifierColumnNames
     * @param string $contentColumnName
     * @return array
     * @throws Exception
     */
    private function generateUpdateData(array $sourceData, array $identifierColumnNames, string $contentColumnName): array {

        $updateData = [];

        $embeddings = $this->embedValues($sourceData, $contentColumnName);

        // Reduce cols to what we're interested in
        foreach ($sourceData as $sourceDatum) {
            $updateDatum = [];
            foreach ($identifierColumnNames as $columnName) {
                $updateDatum[$columnName] = $sourceDatum[$columnName];
            }

            $contentItem = $sourceDatum[$contentColumnName];

            $updateDatum[$contentColumnName] = $contentItem;
            $updateDatum["embedding"] = $embeddings[$contentItem];

            $updateData[] = $updateDatum;
        }
        return $updateData;

    }

    /**
     * @param array $sourceData
     * @param string $contentColumnName
     * @return array
     * @throws Exception
     */
    private function embedValues(array $sourceData, string $contentColumnName): array {

        $valuesToEmbed = array_map(function ($sourceDatum) use ($contentColumnName) {
            return $sourceDatum[$contentColumnName];
        }, $sourceData);

        $embeddings = $this->embeddingService->embedStrings($valuesToEmbed);
        $embeddings = array_map(function ($embedding) {
            return json_encode($embedding);
        }, $embeddings);

        // Format the embeddings to [phrase => embedding]
        return array_combine($valuesToEmbed, $embeddings);
    }

    /**
     * @param DataProcessorInstance $instance
     * @return void
     */
    public function onInstanceDelete($instance): void {

        /**
         * @var VectorDatasetProcessorConfiguration $config
         */
        $instanceKey = $instance->getKey();
        $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($instanceKey);

        $datasourceInstance->remove();

    }

    #[\Override] public function onInstanceSave($instance) {
        // TODO: Implement onInstanceSave() method.
    }

    #[\Override] public function onRelatedObjectSave($instance, $relatedObject) {
        // TODO: Implement onRelatedObjectSave() method.
    }
}
