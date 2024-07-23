<?php

namespace Kinintel\Services\DataProcessor\Query;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\Query\QueryCachingDataProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Caching\CachingDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;

class QueryCachingDataProcessor implements DataProcessor {

    public function __construct(
        private AuthenticationCredentialsService $authenticationService,
        private DatasourceService $datasourceService,
        private DatasetService $datasetService
    ) {
    }


    /**
     * Return the configuration class
     *
     * @return string
     */
    public function getConfigClass() {
        return QueryCachingDataProcessorConfiguration::class;
    }

    /**
     * @param DataProcessorInstance $instance
     * @return void
     */
    public function process($instance): void {

        /**
         * @var QueryCachingDataProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        // Set up variables
        $accountId = $instance->getAccountId();
        $projectKey = $instance->getProjectKey();

        $credentialsKey = Configuration::readParameter("querycache.datasource.credentials.key");
        $tablePrefix = Configuration::readParameter("querycache.datasource.table.prefix");

        $queryId = $config->getSourceQueryId();
        $sourceQuery = $this->datasetService->getDataSetInstance($queryId);

        $params = $sourceQuery->getParameters();

        // Create the columns for the cache table
        $columns = $this->datasetService->getEvaluatedDataSetForDataSetInstance($sourceQuery)->getColumns();
        $columns = array_map(fn($col) => new DatasourceUpdateField(
            $col->getName(), $col->getTitle(), $col->getValueExpression(), $col->getType()
        ), $columns);

        // Add primary key as specified
        foreach ($columns as &$column) {
            if (in_array($column->getName(), $config->getPrimaryKeyColumnNames()))
                $column->setKeyField(true);
        }

        // Include the mandatory caching fields
        $cachingCols = [
            new DatasourceUpdateField(
                name: "parameters",
                keyField: true
            ),
            new DatasourceUpdateField(
                name: "cached_time",
                type: Field::TYPE_DATE_TIME,
                keyField: true
            )];

        // Default value if there aren't parameters supplied
        if (!$params)
            $cachingCols[0]->setValueExpression("1");

        $columns = array_merge($cachingCols, $columns);

        // Create an index on parameters + cached_time
        $indexes = [
            new Index(["parameters", "cached_time"])
        ];

        // Create the cache datasource
        $cacheKey = $instance->getKey()."_cache";
        $cacheTitle = $sourceQuery->getTitle() . " Cache";
        $cacheTableName = $tablePrefix . $cacheKey;
        $cacheConfig = new ManagedTableSQLDatabaseDatasourceConfig(
            source: "table",
            tableName: $cacheTableName,
            columns: $columns,
            indexes: $indexes
        );

        $cacheDatasourceInstance = new DatasourceInstance(
            key: $cacheKey,
            title: $cacheTitle,
            type: "querycache",
            config: $cacheConfig,
            credentialsKey: $credentialsKey
        );
        $cacheDatasourceInstance->setAccountId($accountId);
        $cacheDatasourceInstance->setProjectKey($projectKey);
        $this->datasourceService->saveDataSourceInstance($cacheDatasourceInstance);

        // Create the wrapper caching datasource
        $cachingKey = $instance->getKey()."_caching";
        $cachingTitle = $sourceQuery->getTitle() . " Caching Datasource";
        $cachingConfig = new CachingDatasourceConfig(
            sourceDatasetId: $queryId,
            cachingDatasourceKey: $cacheKey,
            cachingDatasource: null,
            cacheExpiryDays: $config->getCacheExpiryDays(),
            cacheHours: $config->getCacheExpiryHours()
        );

        $cachingDatasource = new DatasourceInstance($cachingKey, $cachingTitle, "caching", $cachingConfig,
            null, null, [], [], $params);
        $cachingDatasource->setAccountId($accountId);
        $cachingDatasource->setProjectKey($projectKey);
        $this->datasourceService->saveDataSourceInstance($cachingDatasource);

    }

    /**
     * Deletes the cache and caching datasource
     *
     * @param DataProcessorInstance $instance
     * @return void
     */
    public function onInstanceDelete($instance) {

        /**
         * @var QueryCachingDataProcessorConfiguration $config
         */
        $config = $instance->returnConfig();
        $queryId = $config->getSourceQueryId();

        $cacheKey = "dataset-{$queryId}-cache";
        $cachingKey = "dataset-{$queryId}-caching-datasource";

        try {
            $this->datasourceService->removeDatasourceInstance($cacheKey);
        } catch (ObjectNotFoundException) {

        }

        try {
            $this->datasourceService->removeDatasourceInstance($cachingKey);
        } catch (ObjectNotFoundException) {

        }

    }

    public function onInstanceSave($instance) {

    }

    public function onRelatedObjectSave($instance, $relatedObject) {

    }
}