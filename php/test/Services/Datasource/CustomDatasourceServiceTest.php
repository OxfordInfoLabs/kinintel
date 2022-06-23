<?php

namespace Kinintel\Test\Services\Datasource;

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
use Kinintel\ValueObjects\Datasource\Configuration\TabularResultsDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

include_once "autoloader.php";

class CustomDatasourceServiceTest extends TestBase {

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
    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->customDatasourceService = new CustomDatasourceService($this->datasourceService);
    }

    public function testCanCreateCustomDatasourceUsingUpdateWithStructureObject() {


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

        $newDatasourceKey = $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, "myproject", 1);

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


        $this->assertTrue($mockDatasource->methodWasCalled("updateFields", [
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ]
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

}
