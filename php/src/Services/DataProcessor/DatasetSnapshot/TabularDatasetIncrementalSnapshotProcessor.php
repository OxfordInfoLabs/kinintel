<?php

namespace Kinintel\Services\DataProcessor\DatasetSnapshot;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\TableMapper\Mapper\TableMapper;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\BaseDataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetIncrementalSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseExpression;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class TabularDatasetIncrementalSnapshotProcessor extends BaseDataProcessor {


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

    const NEWER_VALUES_RULE_TO_FILTER_TYPE = [
        TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER => Filter::FILTER_TYPE_GREATER_THAN,
        TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER_OR_EQUAL => Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO,
        TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_LESSER => Filter::FILTER_TYPE_LESS_THAN,
        TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_LESSER_OR_EQUAL => Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO
    ];


    /**
     * Construct with dependencies
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
     * Get the config class for the incremental snapshot processor
     *
     * @return void
     */
    public function getConfigClass() {
        return TabularDatasetIncrementalSnapshotProcessorConfiguration::class;
    }

    /**
     * Process an instance
     *
     * @param DataProcessorInstance $instance
     */
    public function process($instance) {

        /**
         * @var TabularDatasetIncrementalSnapshotProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        // Read the source dataset instance
        $sourceDataSetInstance = $this->datasetService->getFullDataSetInstance($instance->getRelatedObjectKey());

        // Grab the newer values field
        $newerValuesField = $config->getNewerValuesFieldName();

        // Derive the reference condition
        $referenceCondition = ($config->getNewerValuesRule() == TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER
            || $config->getNewerValuesRule() == TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER_OR_EQUAL)
            ? SummariseExpression::EXPRESSION_TYPE_MAX : SummariseExpression::EXPRESSION_TYPE_MIN;

        // Lookup any previous reference value using configured rule
        $referenceValue = null;
        try {
            $referenceMatches = $this->datasourceService->getEvaluatedDataSourceByInstanceKey($instance->getKey(), [], [
                new TransformationInstance("summarise", new SummariseTransformation([], [
                    new SummariseExpression($referenceCondition, $newerValuesField, null, "snapshotLastValue")
                ]))
            ]);
            $referenceValue = $referenceMatches->getAllData()[0]["snapshotLastValue"] ?? null;
        } catch (ObjectNotFoundException $e) {
        }

        // If reference value, set up transformations for the query
        if ($referenceValue) {

            $filterType = self::NEWER_VALUES_RULE_TO_FILTER_TYPE[$config->getNewerValuesRule()] ?? Filter::FILTER_TYPE_GREATER_THAN;
            $filterTransformations = [new TransformationInstance("filter",
                new FilterTransformation([
                    new Filter("[[" . $newerValuesField . "]]", $referenceValue, $filterType)
                ]))];
        } else {
            $filterTransformations = [];
        }


        $offset = 0;
        $limit = $config->getReadChunkSize() !== null ? $config->getReadChunkSize() : self::DATA_LIMIT;
        $datasource = null;
        $fields = null;
        $keyColumnNames = null;
        do {

            // Evaluate the data set
            $dataset = $this->datasetService->getEvaluatedDataSetForDataSetInstanceById($instance->getRelatedObjectKey(), $config->getParameterValues() ?? [], $filterTransformations, $offset, $limit);
            $results = $dataset->getAllData();


            // If first time, ensure fields exist
            if (!$datasource) {

                $fields = $dataset->getColumns();

                // Eliminate any existing snapshot_item_id or snapshot_date columns from source to avoid duplication
                for ($i = sizeof($fields) - 1; $i >= 0; $i--) {
                    if ($fields[$i]->getName() == "snapshot_item_id" || $fields[$i]->getName() == "snapshot_date")
                        array_splice($fields, $i, 1, []);
                }

                $keyColumnNames = $config->getKeyFieldNames() ?: ObjectArrayUtils::getMemberValueArrayForObjects("name", $fields);

                array_unshift($fields, new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true));


                // Grab indexes
                $indexes = $config->getIndexes() ?: [];

                $datasource = $this->ensureDatasource($instance->getKey(), $instance->getTitle(),
                    $config,
                    $fields, $indexes, $sourceDataSetInstance->getAccountId(), $sourceDataSetInstance->getProjectKey());
            }


            // Construct hashed item id for each item
            foreach ($results as $index => $result) {

                if (isset($results[$index]["snapshot_date"])) unset($results[$index]["snapshot_date"]);

                $rawKeyValue = "";
                foreach ($keyColumnNames as $columnName) {
                    $rawKeyValue .= $result[$columnName] ?? "";
                }
                $results[$index]["snapshot_item_id"] = hash("sha512", $rawKeyValue);
            }


            // Do an update
            $datasource->update(new ArrayTabularDataset($fields, $results), UpdatableDatasource::UPDATE_MODE_REPLACE);

            // Add data limit to the offset to continue
            $offset += $limit;

        } while (sizeof($results) >= $limit);

    }


    /**
     * Ensure the datasource exists, or create
     *
     * @param $instanceKey
     * @param $instanceTitle
     * @param TabularDatasetIncrementalSnapshotProcessorConfiguration $instanceConfig
     * @param $fields
     * @param $accountId
     * @param $projectKey
     *
     * @return SQLDatabaseDatasource
     */
    private function ensureDatasource($instanceKey, $instanceTitle, $instanceConfig, $fields, $indexes, $accountId, $projectKey) {

        // Grab config options
        $credentialsKey = Configuration::readParameter("snapshot.datasource.credentials.key");
        $tablePrefix = Configuration::readParameter("snapshot.datasource.table.prefix");

        try {
            $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($instanceKey);
            $config = $datasourceInstance->getConfig();
            $config["columns"] = $fields;
            $config["indexes"] = $indexes;
            $datasourceInstance->setConfig($config);
        } catch (ObjectNotFoundException $e) {
            $datasourceInstance = new DatasourceInstance($instanceKey, $instanceTitle, "snapshot",
                new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, $tablePrefix . $instanceKey, null, $fields, true, $indexes), $credentialsKey);
            $datasourceInstance->setAccountId($accountId);
            $datasourceInstance->setProjectKey($projectKey);

        }


        // Save and return the datasource instance
        $datasourceInstance = $this->datasourceService->saveDataSourceInstance($datasourceInstance);
        return $datasourceInstance->returnDataSource();
    }

    /**
     * Clean up on delete
     *
     * @param DataProcessorInstance $instance
     * @return void
     */
    public function onInstanceDelete($instance) {

        // Now attempt delete of the datasource
        try {
            $this->datasourceService->removeDatasourceInstance($instance->getKey());
        } catch (ObjectNotFoundException $e) {
            // No probs
        }

    }

}