<?php

namespace Kinintel\Test\Services\Datasource;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\CustomDatasourceService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Document\DocumentDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\TabularResultsDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceConfigUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

include_once "autoloader.php";

class CustomDatasourceServiceTest extends TestBase
{

    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var CustomDatasourceService
     */
    private $customDatasourceService;


    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->customDatasourceService = new CustomDatasourceService($this->datasourceService);
    }

    public function testCanCreateCustomDatasourceUsingUpdateWithStructureObject()
    {


        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDatasourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);
        $mockInstance->returnValue("returnDataSource", $mockDatasource);
        $mockDatasource->returnValue("getConfig", $mockDatasourceConfig);
        $this->datasourceService->returnValue("saveDataSourceInstance", $mockInstance);

        $newDatasourceKey = $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, null, "myproject", 1);

        $expectedDatasourceInstance = new DatasourceInstance($newDatasourceKey, "Hello world", "custom", [
            "source" => "table",
            "tableName" => "custom." . $newDatasourceKey,
            "columns" => [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ], "test");
        $expectedDatasourceInstance->setAccountId(1);
        $expectedDatasourceInstance->setProjectKey("myproject");

        // Check datasource was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedDatasourceInstance
        ]));


        $addDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $this->assertTrue($mockDatasource->methodWasCalled("update", [
            $addDatasource, UpdatableDatasource::UPDATE_MODE_ADD
        ]));


    }

    public function testIfDatasourceKeySuppliedItIsUsedOnCreateCustomDatasourceUsingUpdateWithStructureObject()
    {


        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDatasourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);
        $mockInstance->returnValue("returnDataSource", $mockDatasource);
        $mockDatasource->returnValue("getConfig", $mockDatasourceConfig);
        $this->datasourceService->returnValue("saveDataSourceInstance", $mockInstance);

        $newDatasourceKey = $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, "bingbango", "myproject", 1);

        $this->assertEquals("bingbango", $newDatasourceKey);

        $expectedDatasourceInstance = new DatasourceInstance($newDatasourceKey, "Hello world", "custom", [
            "source" => "table",
            "tableName" => "custom.bingbango",
            "columns" => [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
        ], "test");
        $expectedDatasourceInstance->setAccountId(1);
        $expectedDatasourceInstance->setProjectKey("myproject");

        // Check datasource was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedDatasourceInstance
        ]));


        $addDatasource = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $this->assertTrue($mockDatasource->methodWasCalled("update", [
            $addDatasource, UpdatableDatasource::UPDATE_MODE_ADD
        ]));


    }


    public function testIfCustomDataSourceCreationFailsInstanceIsDeleted()
    {

        $datasourceUpdate = new DatasourceUpdateWithStructure("Hello world", [
            new Field("name"),
            new Field("age", null, null, Field::TYPE_INTEGER)
        ], [
            ["name" => "Joe Bloggs", "age" => 12],
            ["name" => "Mary Jane", "age" => 7]
        ]);


        $mockInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $mockDatasourceConfig = MockObjectProvider::instance()->getMockInstance(TabularResultsDatasourceConfig::class);
        $mockInstance->returnValue("returnDataSource", $mockDatasource);
        $mockDatasource->returnValue("getConfig", $mockDatasourceConfig);
        $this->datasourceService->throwException("saveDataSourceInstance", new \Exception("RANDOM FAILURE"));

        try {
            $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, null, "myproject", 1);
            $this->fail("Should have thrown here");
        } catch (\Exception $e) {
            $this->assertEquals("RANDOM FAILURE", $e->getMessage());
            $this->assertTrue($this->datasourceService->methodWasCalled("removeDatasourceInstance", [
                "custom_data_set_1_" . date("U")
            ]));
        }


    }

    public function testCanCreateDocumentDatasourceInstanceAndIndexDatasourceInstance()
    {
        $fields = [
            new Field("document_file_name", "Document File Name", null, Field::TYPE_STRING, true),
            new Field("phrase", "Phrase", null, Field::TYPE_STRING, true),
            new Field("phrase_length", "Phrase Length", null, Field::TYPE_INTEGER),
            new Field("frequency", "Frequency", null, Field::TYPE_INTEGER)
        ];

        $documentDatasourceConfig = ["tableName" => Configuration::readParameter("custom.datasource.table.prefix") . "document_data_set_4_" . date("U"),
            "storeOriginal" => true, "storeText" => true, "indexContent" => true];
        $documentIndexDatasourceConfig = ["tableName" => Configuration::readParameter("custom.datasource.table.prefix") . "index_document_data_set_4_" . date("U"),
            "source" => "table", "columns" => $fields];

        $expectedInstance = new DatasourceInstance("document_data_set_4_" . date("U"), "TheBestTitle", "document",
            $documentDatasourceConfig, Configuration::readParameter("custom.datasource.credentials.key"));
        $expectedIndexInstance = new DatasourceInstance("index_document_data_set_4_" . date("U"), "TheBestTitle Index",
            "sqldatabase", $documentIndexDatasourceConfig, Configuration::readParameter("custom.datasource.credentials.key"));

        $expectedInstance->setAccountId(4);
        $expectedInstance->setProjectKey("theBestKey");

        $expectedIndexInstance->setAccountId(4);
        $expectedIndexInstance->setProjectKey("theBestKey");

        $updateConfig = new DatasourceConfigUpdate("TheBestTitle", $documentDatasourceConfig);

        $expectedInstanceKey = $this->customDatasourceService->createDocumentDatasourceInstance($updateConfig, "theBestKey", 4);

        $this->assertEquals($expectedInstance, $this->datasourceService->getMethodCallHistory("saveDataSourceInstance")[0][0]);
        $this->assertEquals($expectedIndexInstance, $this->datasourceService->getMethodCallHistory("saveDataSourceInstance")[1][0]);

        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [$expectedInstance]));
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [$expectedIndexInstance]));
        $this->assertEquals("document_data_set_4_" . date("U"), $expectedInstanceKey);
    }

    public function testCanCreateTabularSnapshotDatasourceInstance()
    {

        $returnInstance = $this->customDatasourceService->createTabularSnapshotDatasourceInstance("My Test Instance",
            [new Field("Field1"), new Field("Field2")], "dummydummy", 53
        );

        $this->assertEquals("snapshot_data_set_53_" . date("U"), $returnInstance->getKey());

        $expectedInstance = new DatasourceInstance("snapshot_data_set_53_" . date("U"), "My Test Instance",
            "snapshot", [
                "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                "tableName" => "snapshot_data_set_53_" . date("U"),
                "columns" => [new Field("Field1"), new Field("Field2")]
            ], "dataset_snapshot");
        $expectedInstance->setProjectKey("dummydummy");
        $expectedInstance->setAccountId(53);


        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedInstance
        ]));

        $this->assertEquals($expectedInstance, $returnInstance);

    }


}
