<?php

namespace Kinintel\Test\Services\DataProcessor\VectorDataset;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\DataProcessor\VectorDataset\VectorDatasetProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding\OpenAIEmbeddingService;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\DataProcessor\Configuration\VectorDataset\VectorDatasetProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class VectorDatasetProcessorTest extends TestCase {

    /**
     * @var VectorDatasetProcessor
     */
    private VectorDatasetProcessor $processor;


    /**
     * @var MockObject
     */
    private $datasetService;


    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var MockObject
     */
    private $embeddingService;

    public function setUp(): void {
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->embeddingService = MockObjectProvider::instance()->getMockInstance(OpenAIEmbeddingService::class);
        $this->processor = new VectorDatasetProcessor($this->datasetService, $this->datasourceService, $this->embeddingService);
    }

    /**
     * @throws \Exception
     */
    public function testVectorDatasourceUpdatedWithDataFromSourceDataset() {

        $datasetInstance = new DatasetInstance(null, 1, null);

        $firstDataset = [];
        for ($i = 0; $i < 10; $i++) {
            $firstDataset[] = ["document" => "doc", "page" => $i + 1, "phrase" => "Item" . $i + 1];
        }

        $firstDatasetExpected = array_map(function ($datum) {
            $datum["embedding"] = "[0,0,{$datum['page']}]";
            return $datum;
        }, $firstDataset);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("page", "Page", null, Field::TYPE_INTEGER, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true)
            ], $firstDataset), [
                $datasetInstance, [], [], 0, 10
            ]);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("page", "Page", null, Field::TYPE_INTEGER, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
            ], [
                ["document" => "doc", "page" => 11, "phrase" => "Item 11"],
                ["document" => "doc", "page" => 12, "phrase" => "Item 12"],
                ["document" => "doc", "page" => 13, "phrase" => "Item 13"]
            ]), [
                $datasetInstance, [], [], 10, 10
            ]);

        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);

        // Get a mock data source instance
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestvectorembedding"
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSource->returnValue("getConfig", new ManagedTableSQLDatabaseDatasourceConfig("table", "test"));
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);
        $mockDataSourceInstance->returnValue("getConfig", [
            "tableName" => "test"
        ]);

        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);

        // Mock embeddings
        for ($i = 0; $i < 10; $i++) {
            $mockEmbeddings[] = [0, 0, $i + 1];
        }
        $mockItemsToEmbed = array_map(function ($mockDatum) {
            return $mockDatum["phrase"];
        }, $firstDataset);
        $this->embeddingService->returnValue("embedStrings", $mockEmbeddings, [$mockItemsToEmbed]);

        // Second call
        $mockEmbeddings2 = [[0, 0, 11], [0, 0, 12], [0, 0, 13]];
        $mockItemsToEmbed2 = ["Item 11", "Item 12", "Item 13"];
        $this->embeddingService->returnValue("embedStrings", $mockEmbeddings2, [$mockItemsToEmbed2]);

        $config = new VectorDatasetProcessorConfiguration(25, null,
            ["document", "page"], "phrase", 10);

        $instance = new DataProcessorInstance("mytestvectorembedding", "need", "vectorembedding", $config);
        $this->processor->process($instance);

        // Check config updated with correct set of fields
        $this->assertTrue($mockDataSourceInstance->methodWasCalled("setConfig", [new ManagedTableSQLDatabaseDatasourceConfig("table", "test", "", [
            new Field("document", "Document", null, Field::TYPE_STRING, true),
            new Field("page", "Page", null, Field::TYPE_INTEGER, true),
            new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
            new Field("embedding", "Embedding", null, Field::TYPE_VECTOR)
        ], false)]));

        // Check that the table was created with simple snapshot_date based PK.
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $mockDataSourceInstance
        ]));

        // Check all data was updated as expected
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("page", "Page", null, Field::TYPE_INTEGER, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
                new Field("embedding", "Embedding", null, Field::TYPE_VECTOR)
            ], $firstDatasetExpected), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

        // Check second page was correctly updated
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("page", "Page", null, Field::TYPE_INTEGER, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
                new Field("embedding", "Embedding", null, Field::TYPE_VECTOR)
            ], [
                ["document" => "doc", "page" => 11, "phrase" => "Item 11", "embedding" => "[0,0,11]"],
                ["document" => "doc", "page" => 12, "phrase" => "Item 12", "embedding" => "[0,0,12]"],
                ["document" => "doc", "page" => 13, "phrase" => "Item 13", "embedding" => "[0,0,13]"]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

    }



    public function testIfIdentifierAndContentColumnTheSameOnlySingleFieldCreatedInTargetDatasource(){

        $datasetInstance = new DatasetInstance(null, 1, null);

        $firstDataset = [];
        for ($i = 0; $i < 10; $i++) {
            $firstDataset[] = ["document" => "doc", "phrase" => ($i + 1)];
        }

        $firstDatasetExpected = array_map(function ($datum) {
            $datum["embedding"] = "[0,0,{$datum['phrase']}]";
            return $datum;
        }, $firstDataset);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("page", "Page", null, Field::TYPE_INTEGER, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true)
            ], $firstDataset), [
                $datasetInstance, [], [], 0, 10
            ]);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("page", "Page", null, Field::TYPE_INTEGER, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
            ], [
                ["document" => "doc", "page" => 11, "phrase" => "11"],
                ["document" => "doc", "page" => 12, "phrase" => "12"],
                ["document" => "doc", "page" => 13, "phrase" => "13"]
            ]), [
                $datasetInstance, [], [], 10, 10
            ]);

        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);

        // Get a mock data source instance
        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
            "mytestvectorembedding"
        ]);


        // Program a mock data source on return
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDataSource->returnValue("getConfig", new ManagedTableSQLDatabaseDatasourceConfig("table", "test"));
        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);
        $mockDataSourceInstance->returnValue("getConfig", [
            "tableName" => "test"
        ]);

        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
        $mockDatabaseConnection = MockObjectProvider::instance()->getMockInstance(DatabaseConnection::class);
        $mockAuthCredentials->returnValue("returnDatabaseConnection", $mockDatabaseConnection);
        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);

        // Mock embeddings
        for ($i = 0; $i < 10; $i++) {
            $mockEmbeddings[] = [0, 0, $i + 1];
        }
        $mockItemsToEmbed = array_map(function ($mockDatum) {
            return $mockDatum["phrase"];
        }, $firstDataset);
        $this->embeddingService->returnValue("embedStrings", $mockEmbeddings, [$mockItemsToEmbed]);

        // Second call
        $mockEmbeddings2 = [[0, 0, 11], [0, 0, 12], [0, 0, 13]];
        $mockItemsToEmbed2 = ["11", "12", "13"];
        $this->embeddingService->returnValue("embedStrings", $mockEmbeddings2, [$mockItemsToEmbed2]);

        $config = new VectorDatasetProcessorConfiguration(25, null,
            ["document", "phrase"], "phrase", 10);

        $instance = new DataProcessorInstance("mytestvectorembedding", "need", "vectorembedding", $config);
        $this->processor->process($instance);


        // Check config updated with correct set of fields
        $this->assertTrue($mockDataSourceInstance->methodWasCalled("setConfig", [new ManagedTableSQLDatabaseDatasourceConfig("table", "test", "", [
            new Field("document", "Document", null, Field::TYPE_STRING, true),
            new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
            new Field("embedding", "Embedding", null, Field::TYPE_VECTOR)
        ], false)]));

        // Check that the table was created with simple snapshot_date based PK.
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $mockDataSourceInstance
        ]));


        // Check all data was updated as expected
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
                new Field("embedding", "Embedding", null, Field::TYPE_VECTOR)
            ], $firstDatasetExpected), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

        // Check second page was correctly updated
        $this->assertTrue($mockDataSource->methodWasCalled("update", [
            new ArrayTabularDataset([
                new Field("document", "Document", null, Field::TYPE_STRING, true),
                new Field("phrase", "Phrase", null, Field::TYPE_LONG_STRING, true),
                new Field("embedding", "Embedding", null, Field::TYPE_VECTOR)
            ], [
                ["document" => "doc",  "phrase" => "11", "embedding" => "[0,0,11]"],
                ["document" => "doc",  "phrase" => "12", "embedding" => "[0,0,12]"],
                ["document" => "doc",  "phrase" => "13", "embedding" => "[0,0,13]"]
            ]), UpdatableDatasource::UPDATE_MODE_REPLACE
        ]));

    }


    // ToDo: Test when using source datasource


//    public function testDoesCreateSQLQueryDatasourceOnRunOfProcessor() {
//
//        $datasetInstanceSummary = new DatasetInstanceSummary("My Source", "my_source");
//        $datasetInstance = new DatasetInstance($datasetInstanceSummary, 1, null);
//
//
//        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance",
//            new ArrayTabularDataset([
//                new Field("someColumn", "Some Column", null, Field::TYPE_STRING, true),
//                new Field("embedding")
//            ], [[
//                "someColumn" => "hey",
//                "embedding" => "[0,0,0]"
//            ]]), [
//                $datasetInstance, [], [], 0, 10
//            ]);
//
//        $this->datasetService->returnValue("getFullDataSetInstance", $datasetInstance, [25]);
//
//        // Get a mock data source instance
//        $mockDataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
//
//        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDataSourceInstance, [
//            "mytestvectorembedding"
//        ]);
//
//
//        // Program a mock data source on return
//        $mockDataSource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
//        $mockDataSource->returnValue("getConfig", new ManagedTableSQLDatabaseDatasourceConfig("table", "test"));
//        $mockDataSourceInstance->returnValue("returnDataSource", $mockDataSource);
//        $mockAuthCredentials = MockObjectProvider::instance()->getMockInstance(SQLDatabaseCredentials::class);
//        $mockDataSource->returnValue("getAuthenticationCredentials", $mockAuthCredentials);
//
//        // Mock embeddings
//        $this->embeddingService->returnValue("embedStrings", ["[0,0,0]"], [["hey"]]);
//
//        $config = new VectorDatasetConfiguration(25, null, "mytestvectorembedding",
//            [], new Field("someColumn"), 10);
//
//        $instance = new DataProcessorInstance("no", "need", "vectorembedding", $config);
//        $this->processor->process($instance);
//
//        $credentialsKey = Configuration::readParameter("vector.datasource.credentials.key");
//
//        $expectedConfig = new SQLDatabaseDatasourceConfig("table", "mytestvectorembedding");
//        $expectedSQLQueryDatasourceInstance = new DatasourceInstance("my_source_embeddings", "My Source Embeddings", "sqldatabase", $expectedConfig, $credentialsKey);
//
//        $this->assertEquals($expectedSQLQueryDatasourceInstance, $this->datasourceService->getMethodCallHistory("saveDataSourceInstance")[1][0]);
//        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [$expectedSQLQueryDatasourceInstance]));
//
//    }

    public function testVectorDatasourceInstanceDeletedOnCallToDeleteMethod() {


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $mockInstance->returnValue("getKey", "mytestkey");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockInstance, "mytestkey");

        $processor = new VectorDatasetProcessor($this->datasetService, $this->datasourceService, $this->embeddingService);
        $processor->onInstanceDelete($mockInstance);

        $this->assertTrue($mockInstance->methodWasCalled("remove"));
    }

}