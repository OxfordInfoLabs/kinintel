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
use Kinintel\Services\DataProcessor\DataProcessor;
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
use Kinintel\ValueObjects\Transformation\Transformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class TabularDatasetIncrementalSnapshotProcessor implements DataProcessor {


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

        // Grab the newer values field
        $newerValuesField = $config->getNewerValuesFieldName();

        // Derive the reference condition
        $referenceCondition = ($config->getNewerValuesRule() == TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER
            || $config->getNewerValuesRule() == TabularDatasetIncrementalSnapshotProcessorConfiguration::LATEST_VALUE_GREATER_OR_EQUAL)
            ? SummariseExpression::EXPRESSION_TYPE_MAX : SummariseExpression::EXPRESSION_TYPE_MIN;

        // Lookup any previous reference value using configured rule
        $referenceValue = null;
        try {
            $referenceMatches = $this->datasourceService->getEvaluatedDataSource($config->getSnapshotIdentifier(), [], [
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
        $datasource = null;
        $fields = null;
        $keyColumnNames = null;
        do {

            // Evaluate the data set
            $dataset = $this->datasetService->getEvaluatedDataSetForDataSetInstanceById($config->getDatasetInstanceId(), [], $filterTransformations, $offset, self::DATA_LIMIT);
            $results = $dataset->getAllData();


            // If first time, ensure fields exist
            if (!$datasource) {

                $fields = $dataset->getColumns();
                array_unshift($fields, new Field("snapshot_item_id", null, null, Field::TYPE_STRING, true));

                $keyColumnNames = $config->getKeyFieldNames() ?: ObjectArrayUtils::getMemberValueArrayForObjects("name", $fields);

                $datasource = $this->ensureDatasource($config->getSnapshotIdentifier(), $instance->getTitle(),
                    $config,
                    $fields);
            }


            // Construct hashed item id for each item
            foreach ($results as $index => $result) {
                $rawKeyValue = "";
                foreach ($keyColumnNames as $columnName) {
                    $rawKeyValue .= $result[$columnName] ?? "";
                }
                $results[$index]["snapshot_item_id"] = hash("sha512", $rawKeyValue);
            }

            // Do an update
            $datasource->update(new ArrayTabularDataset($fields, $results), UpdatableDatasource::UPDATE_MODE_REPLACE);

        } while (sizeof($results) == self::DATA_LIMIT);

    }


    /**
     * Ensure the datasource exists, or create
     *
     * @param $instanceKey
     * @param $instanceTitle
     * @param TabularDatasetIncrementalSnapshotProcessorConfiguration $instanceConfig
     * @param $fields
     *
     * @return SQLDatabaseDatasource
     */
    private function ensureDatasource($instanceKey, $instanceTitle, $instanceConfig, $fields) {

        // Grab config options
        $credentialsKey = Configuration::readParameter("snapshot.datasource.credentials.key");
        $tablePrefix = Configuration::readParameter("snapshot.datasource.table.prefix");

        try {
            $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($instanceKey);
            $datasourceInstance->returnConfig()->setColumns($fields);
        } catch (ObjectNotFoundException $e) {
            $datasourceInstance = new DatasourceInstance($instanceKey, $instanceTitle, "snapshot",
                new ManagedTableSQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, $tablePrefix . $instanceKey, null, $fields, true), $credentialsKey);
        }

        // Save and return the datasource instance
        $datasourceInstance = $this->datasourceService->saveDataSourceInstance($datasourceInstance);
        return $datasourceInstance->returnDataSource();
    }

}