<?php

namespace Kinintel\Test\Services\DataProcessor\Query;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\DataProcessor\Query\QueryCachingDataProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\DataProcessor\Configuration\Query\QueryCachingDataProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Caching\CachingDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Parameter\Parameter;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class QueryCachingDataProcessorTest extends TestCase {

    /**
     * @var MockObject
     */
    private $authCreds;

    /**
     * @var MockObjectProvider
     */
    private $datasourceService;

    /**
     * @var MockObjectProvider
     */
    private $datasetService;

    public function setUp(): void {
        $this->authCreds = MockObjectProvider::instance()->getMockInstance(AuthenticationCredentialsInstance::class);
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
    }

    public function testDoesCreateCacheTableAndDatasourceAndCachingDatasourceCorrectlyWithoutParameters() {

        $processorConfig = new QueryCachingDataProcessorConfiguration(25, 1, 12, ["col1"]);

        $sourceDatasetSummary = new DatasetInstanceSummary(
            title: "My Query",
            datasourceInstanceKey: "some_source"
        );


        $mockDataset = MockObjectProvider::instance()->getMockInstance(ArrayTabularDataset::class);
        $mockDataset->returnValue("getColumns", [
            new Field("col1"),
            new Field("col2")
        ]);

        $this->datasetService->returnValue("getDataSetInstance", $sourceDatasetSummary, [25]);
        $this->datasetService->returnValue("getEvaluatedParameters", [], $sourceDatasetSummary);
        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", $mockDataset, [$sourceDatasetSummary]);


        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $processorInstance->returnValue("getAccountId", 1);
        $processorInstance->returnValue("getProjectKey", "my_project");
        $processorInstance->returnValue("getKey", "querycache_12345");


        $processor = new QueryCachingDataProcessor($this->authCreds, $this->datasourceService, $this->datasetService);
        $processor->process($processorInstance);

        $history = $this->datasourceService->getMethodCallHistory("saveDataSourceInstance");

        $expectedCacheConfig = new ManagedTableSQLDatabaseDatasourceConfig(
            source: "table",
            tableName: "query_cache.querycache_12345_cache",
            columns: [
                new DatasourceUpdateField(
                    name: "parameters",
                    valueExpression: "1",
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "cached_time",
                    type: Field::TYPE_DATE_TIME,
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "col1",
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "col2"
                )
            ],
            indexes: [
                new Index(["parameters", "cached_time"])
            ]
        );

        $expectedCacheDatasourceInstance = new DatasourceInstance("querycache_12345_cache", "My Query Cache",
            "querycache", $expectedCacheConfig, "test");
        $expectedCacheDatasourceInstance->setAccountId(1);
        $expectedCacheDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCacheDatasourceInstance, $history[0][0]);


        $expectedCachingConfig = new CachingDatasourceConfig(
            sourceDatasetId: 25,
            cachingDatasourceKey: "querycache_12345_cache",
            cacheExpiryDays: 1,
            cacheHours: 12
        );

        $expectedCachingDatasourceInstance = new DatasourceInstance("querycache_12345_caching", "My Query Caching Datasource",
            "caching", $expectedCachingConfig);
        $expectedCachingDatasourceInstance->setAccountId(1);
        $expectedCachingDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCachingDatasourceInstance, $history[1][0]);

    }

    public function testDoesPassThroughParametersOnSourceQueryWhenCreatingCachingDatasource() {

        $processorConfig = new QueryCachingDataProcessorConfiguration(25, 1, 12);
        $params = [new Parameter("param", "Param")];

        $sourceDatasetSummary = new DatasetInstanceSummary(
            title: "My Query",
            datasourceInstanceKey: "some_source",
            parameters: $params
        );


        $mockDataset = MockObjectProvider::instance()->getMockInstance(ArrayTabularDataset::class);
        $mockDataset->returnValue("getColumns", [
            new Field("col1"),
            new Field("col2")
        ]);

        $this->datasetService->returnValue("getDataSetInstance", $sourceDatasetSummary, [25]);
        $this->datasetService->returnValue("getEvaluatedParameters", $params, $sourceDatasetSummary);
        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", $mockDataset, [$sourceDatasetSummary]);


        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $processorInstance->returnValue("getAccountId", 1);
        $processorInstance->returnValue("getProjectKey", "my_project");
        $processorInstance->returnValue("getKey", "querycache_12345");


        $processor = new QueryCachingDataProcessor($this->authCreds, $this->datasourceService, $this->datasetService);
        $processor->process($processorInstance);

        $history = $this->datasourceService->getMethodCallHistory("saveDataSourceInstance");

        $expectedCacheConfig = new ManagedTableSQLDatabaseDatasourceConfig(
            source: "table",
            tableName: "query_cache.querycache_12345_cache",
            columns: [
                new DatasourceUpdateField(
                    name: "parameters",
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "cached_time",
                    type: Field::TYPE_DATE_TIME,
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "col1"
                ),
                new DatasourceUpdateField(
                    name: "col2"
                )
            ],
            indexes: [
                new Index(["parameters", "cached_time"])
            ]
        );


        $expectedCacheDatasourceInstance = new DatasourceInstance(
            key: "querycache_12345_cache",
            title: "My Query Cache",
            type: "querycache",
            config: $expectedCacheConfig,
            credentialsKey: "test"
        );

        $expectedCacheDatasourceInstance->setAccountId(1);
        $expectedCacheDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCacheDatasourceInstance, $history[0][0]);


        $expectedCachingConfig = new CachingDatasourceConfig(
            sourceDatasetId: 25,
            cachingDatasourceKey: "querycache_12345_cache",
            cacheExpiryDays: 1,
            cacheHours: 12
        );

        $expectedCachingDatasourceInstance = new DatasourceInstance(
            key: "querycache_12345_caching",
            title: "My Query Caching Datasource",
            type: "caching",
            config: $expectedCachingConfig,
            parameters: $params
        );
        $expectedCachingDatasourceInstance->setAccountId(1);
        $expectedCachingDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCachingDatasourceInstance, $history[1][0]);

    }

    public function testDoesPassThroughParametersFromUnderlyingQueriesWhenCreatingCache() {

        $processorConfig = new QueryCachingDataProcessorConfiguration(10, null, 12);
        $params = [new Parameter("param", "Param")];

        $extendedDatasetSummary = new DatasetInstanceSummary(
            title: "My Query",
            datasourceInstanceKey: "some_source",
            parameters: [],
            parameterValues: ["param" => "hello"],
            sourceDataSet: "base_query"
        );


        $mockExtendedDataset = MockObjectProvider::instance()->getMockInstance(ArrayTabularDataset::class);
        $mockExtendedDataset->returnValue("getColumns", [
            new Field("baseCol1"),
            new Field("baseCol2"),
            new Field("newCol")
        ]);

        $this->datasetService->returnValue("getDataSetInstance", $extendedDatasetSummary, [10]);
        $this->datasetService->returnValue("getEvaluatedParameters", $params, $extendedDatasetSummary);
        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", $mockExtendedDataset, [$extendedDatasetSummary]);


        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $processorInstance->returnValue("getAccountId", 1);
        $processorInstance->returnValue("getProjectKey", "my_project");
        $processorInstance->returnValue("getKey", "querycache_12345");


        $processor = new QueryCachingDataProcessor($this->authCreds, $this->datasourceService, $this->datasetService);
        $processor->process($processorInstance);

        $history = $this->datasourceService->getMethodCallHistory("saveDataSourceInstance");

        $expectedCacheConfig = new ManagedTableSQLDatabaseDatasourceConfig(
            source: "table",
            tableName: "query_cache.querycache_12345_cache",
            columns: [
                new DatasourceUpdateField(
                    name: "parameters",
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "cached_time",
                    type: Field::TYPE_DATE_TIME,
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "baseCol1"
                ),
                new DatasourceUpdateField(
                    name: "baseCol2"
                ),
                new DatasourceUpdateField(
                    name: "newCol"
                )
            ],
            indexes: [
                new Index(["parameters", "cached_time"])
            ]
        );


        $expectedCacheDatasourceInstance = new DatasourceInstance(
            key: "querycache_12345_cache",
            title: "My Query Cache",
            type: "querycache",
            config: $expectedCacheConfig,
            credentialsKey: "test"
        );

        $expectedCacheDatasourceInstance->setAccountId(1);
        $expectedCacheDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCacheDatasourceInstance, $history[0][0]);


        $expectedCachingConfig = new CachingDatasourceConfig(
            sourceDatasetId: 10,
            cachingDatasourceKey: "querycache_12345_cache",
            cacheExpiryDays: null,
            cacheHours: 12
        );

        $expectedCachingDatasourceInstance = new DatasourceInstance(
            key: "querycache_12345_caching",
            title: "My Query Caching Datasource",
            type: "caching",
            config: $expectedCachingConfig,
            parameters: $params
        );
        $expectedCachingDatasourceInstance->setAccountId(1);
        $expectedCachingDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCachingDatasourceInstance, $history[1][0]);


    }

    public function testCanHandleSourceAutoIncrementFields() {

        $processorConfig = new QueryCachingDataProcessorConfiguration(3, null, 12, ["col2"]);
        $params = [new Parameter("param", "Param")];

        $sourceDatasetSummary = new DatasetInstanceSummary(
            title: "My Query",
            datasourceInstanceKey: "some_source",
            parameters: $params
        );


        $mockDataset = MockObjectProvider::instance()->getMockInstance(ArrayTabularDataset::class);
        $mockDataset->returnValue("getColumns", [
            new Field(name: "col1", type: Field::TYPE_ID),
            new Field(name: "col2", type: Field::TYPE_STRING, keyField: true)
        ]);

        $this->datasetService->returnValue("getDataSetInstance", $sourceDatasetSummary, [3]);
        $this->datasetService->returnValue("getEvaluatedParameters", $params, $sourceDatasetSummary);
        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", $mockDataset, [$sourceDatasetSummary]);


        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $processorConfig);
        $processorInstance->returnValue("getAccountId", 1);
        $processorInstance->returnValue("getProjectKey", "my_project");
        $processorInstance->returnValue("getKey", "querycache_12345");


        $processor = new QueryCachingDataProcessor($this->authCreds, $this->datasourceService, $this->datasetService);
        $processor->process($processorInstance);

        $history = $this->datasourceService->getMethodCallHistory("saveDataSourceInstance");

        $expectedCacheConfig = new ManagedTableSQLDatabaseDatasourceConfig(
            source: "table",
            tableName: "query_cache.querycache_12345_cache",
            columns: [
                new DatasourceUpdateField(
                    name: "parameters",
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "cached_time",
                    type: Field::TYPE_DATE_TIME,
                    keyField: true
                ),
                new DatasourceUpdateField(
                    name: "col1",
                    type: Field::TYPE_INTEGER
                ),
                new DatasourceUpdateField(
                    name: "col2",
                    type: Field::TYPE_STRING,
                    keyField: true
                )
            ],
            indexes: [
                new Index(["parameters", "cached_time"])
            ]
        );


        $expectedCacheDatasourceInstance = new DatasourceInstance(
            key: "querycache_12345_cache",
            title: "My Query Cache",
            type: "querycache",
            config: $expectedCacheConfig,
            credentialsKey: "test"
        );

        $expectedCacheDatasourceInstance->setAccountId(1);
        $expectedCacheDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCacheDatasourceInstance, $history[0][0]);


        $expectedCachingConfig = new CachingDatasourceConfig(
            sourceDatasetId: 3,
            cachingDatasourceKey: "querycache_12345_cache",
            cacheExpiryDays: null,
            cacheHours: 12
        );

        $expectedCachingDatasourceInstance = new DatasourceInstance(
            key: "querycache_12345_caching",
            title: "My Query Caching Datasource",
            type: "caching",
            config: $expectedCachingConfig,
            parameters: $params
        );
        $expectedCachingDatasourceInstance->setAccountId(1);
        $expectedCachingDatasourceInstance->setProjectKey("my_project");
        $this->assertEquals($expectedCachingDatasourceInstance, $history[1][0]);

    }

    public function testDoesCallCorrectDeleteMethodsWhenOnInstanceDelete() {

        $processorConfig = new QueryCachingDataProcessorConfiguration(25, 1, 12);
        $processor = new QueryCachingDataProcessor($this->authCreds, $this->datasourceService, $this->datasetService);

        $instance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $instance->returnValue("returnConfig", $processorConfig);

        $processor->onInstanceDelete($instance);

        $this->assertTrue($this->datasourceService->methodWasCalled("removeDatasourceInstance", ["dataset-25-cache"]));
        $this->assertTrue($this->datasourceService->methodWasCalled("removeDatasourceInstance", ["dataset-25-caching-datasource"]));

    }

}