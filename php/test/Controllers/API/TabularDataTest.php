<?php

namespace Kinintel\Test\Controllers\API;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;
use Kinintel\Controllers\API\TabularData;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class TabularDataTest extends TestBase {

    /**
     * @var TabularData
     */
    private $tabularData;

    /**
     * @var MockObject
     */
    private $datasourceService;


    /**
     * @var DatasourceInstance
     */
    private $datasourceInstance;


    public function setUp(): void {

        parent::setUp();

        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->tabularData = new TabularData($this->datasourceService);

        $this->datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig("table", "test_data", "", [new Field("test"), new Field("test2")]), null, null);
        $this->datasourceInstance->returnValue("returnDataSource", $datasource);

        $this->datasourceService->returnValue("getDataSourceInstanceByImportKey", $this->datasourceInstance, ["bingo"]);
    }


    public function testCanListAllDataWithLimitsAndOffsets() {


        // Default behaviour - no limits or offsets
        $arrayDataset = new ArrayTabularDataset([new Field("test")], [["test" => 1]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [], 0, 100
        ]);

        $this->assertEquals([["test" => 1]], $this->tabularData->list("bingo", $request));


        // Offset and limit defined
        $arrayDataset = new ArrayTabularDataset([new Field("test")], [["test" => 2]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "offset" => 5,
            "limit" => 10
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [], 5, 10
        ]);

        $this->assertEquals([["test" => 2]], $this->tabularData->list("bingo", $request));


    }


    public function testCanFilterListDataWithLimitsAndOffsets() {


        // Default behaviour - no limits or offsets
        $arrayDataset = new ArrayTabularDataset([new Field("test")], [["test" => 1]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "a" => 1,
            "b" => "hello"
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("[[a]]", 1),
                    new Filter("[[b]]", "hello")
                ]))
            ], 0, 100
        ]);

        $this->assertEquals([["test" => 1]], $this->tabularData->list("bingo", $request));


        // Offset and limit defined
        $arrayDataset = new ArrayTabularDataset([new Field("test")], [["test" => 2]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "a" => 1,
            "b" => "hello",
            "offset" => 5,
            "limit" => 10
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("[[a]]", 1),
                    new Filter("[[b]]", "hello")
                ]))
            ], 5, 10
        ]);

        $this->assertEquals([["test" => 2]], $this->tabularData->list("bingo", $request));


    }


    public function testFilteredDeleteConvertsSimplifiedFilterSyntaxIntoFilterJunction() {

        // Simple equals filters
        $this->tabularData->filteredDelete("bingo", [["column" => "test", "value" => 3], ["column" => "test2", "value" => "Mark"]]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", 3), new Filter("[[test2]]", "Mark")])]));


        // In filters for arrays
        $this->tabularData->filteredDelete("bingo", [["column" => "test", "value" => [3, 5, 7]], ["column" => "test2", "value" => ["Mark", "Bob"]]]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", [3, 5, 7], Filter::FILTER_TYPE_IN), new Filter("[[test2]]", ["Mark", "Bob"], Filter::FILTER_TYPE_IN)])]));


        // Advanced filtering
        $this->tabularData->filteredDelete("bingo", [["column" => "test", "value" => "55", "matchType" => Filter::FILTER_TYPE_GREATER_THAN], ["column" => "test2", "value" => "Mark", "matchType" => Filter::FILTER_TYPE_LIKE]]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", 55, Filter::FILTER_TYPE_GREATER_THAN), new Filter("[[test2]]", "Mark", Filter::FILTER_TYPE_LIKE)])]));


    }

    /**
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testColumnNotFoundExceptionRaisedIfNoneExistentFieldReferencedInFilteredDelete() {

        // Simple equals filters
        try {
            $this->tabularData->filteredDelete("bingo", ["bad" => 3, "poorer" => "Mark"]);
            $this->fail("Should have thrown here");
        } catch (DatasourceUpdateException $e) {

        }

    }


}