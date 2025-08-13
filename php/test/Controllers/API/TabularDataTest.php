<?php

namespace Kinintel\Test\Controllers\API;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;
use Kinintel\Controllers\API\TabularData;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Exception\FieldNotFoundException;
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
use Kinintel\ValueObjects\Transformation\Filter\FilterType;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\Sort;
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
        $config = new SQLDatabaseDatasourceConfig("table", "test_data", "", [new Field("test"), new Field("test2")]);

        $datasource = new SQLDatabaseDatasource($config, null, null);
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
        $arrayDataset = new ArrayTabularDataset([new Field("test", new Field("test2"))], [["test" => 1]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "filter_test" => 1,
            "filter_test2" => "hello"
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("[[test]]", 1),
                    new Filter("[[test2]]", "hello")
                ]))
            ], 0, 100
        ]);

        $this->assertEquals([["test" => 1]], $this->tabularData->list("bingo", $request));


        // Offset and limit defined
        $arrayDataset = new ArrayTabularDataset([new Field("test")], [["test" => 2]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "filter_test" => 1,
            "filter_test2" => "hello",
            "offset" => 5,
            "limit" => 10
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("[[test]]", 1),
                    new Filter("[[test2]]", "hello")
                ]))
            ], 5, 10
        ]);

        $this->assertEquals([["test" => 2]], $this->tabularData->list("bingo", $request));


    }

    public function testCanSupplyFilterTypesAsPipeSeperatedSuffixesForFiltersWhenListingData() {


        // Default behaviour - no limits or offsets
        $arrayDataset = new ArrayTabularDataset([new Field("test"), new Field("test2")], [["test" => 1]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "filter_test" => "1|gt",
            "filter_test2" => "*hello*|like"
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("[[test]]", 1,FilterType::gt),
                    new Filter("[[test2]]", "*hello*", FilterType::like)
                ]))
            ], 0, 100
        ]);

        $this->assertEquals([["test" => 1, "test2" => null]], $this->tabularData->list("bingo", $request));

    }

    public function testCanSortByOneOrMoreFieldsWithOptionalPipeSuffixedDirections(){

        // Simple default sort
        $arrayDataset = new ArrayTabularDataset([new Field("test"), new Field("test2")], [["test" => 1]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "filter_test" => "1|gt",
            "filter_test2" => "*hello*|like",
            "sort" => "test"
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("[[test]]", 1,FilterType::gt),
                    new Filter("[[test2]]", "*hello*", FilterType::like)
                ])),
                new TransformationInstance("multisort", new MultiSortTransformation([
                    new Sort("test", Sort::DIRECTION_ASC)
                ]))
            ], 0, 100
        ]);

        $this->assertEquals([["test" => 1, "test2" => null]], $this->tabularData->list("bingo", $request));


        // More elaborate sort
        $arrayDataset = new ArrayTabularDataset([new Field("test")], [["test" => 2]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "filter_test" => "1|gt",
            "filter_test2" => "*hello*|like",
            "sort" => "test|desc|test2"
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource", $arrayDataset, [
            $this->datasourceInstance, [], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("[[test]]", 1,FilterType::gt),
                    new Filter("[[test2]]", "*hello*", FilterType::like)
                ])),
                new TransformationInstance("multisort", new MultiSortTransformation([
                    new Sort("test", Sort::DIRECTION_DESC),
                    new Sort("test2", Sort::DIRECTION_ASC),
                ]))
            ], 0, 100
        ]);

        $this->assertEquals([["test" => 2]], $this->tabularData->list("bingo", $request));
        
    }

    public function testInvalidColumnsSuppliedToFilterOrSortAreDetectedAndExceptionRaised(){

        // Simple default sort
        $arrayDataset = new ArrayTabularDataset([new Field("test")], [["test" => 1]]);

        $request = MockObjectProvider::instance()->getMockInstance(Request::class);
        $request->returnValue("getParameters", [
            "filter_a" => "1|gt",
            "filter_b" => "*hello*|like",
            "sort" => "c"
        ]);

        try {
            $this->tabularData->list("bingo", $request);
            $this->fail("Should have thrown here");
        } catch (FieldNotFoundException $e){
            $this->assertEquals(new FieldNotFoundException("a", "column", "filtering"), $e);
        }

        $request->returnValue("getParameters", [
            "filter_test" => "1|gt",
            "filter_b" => "*hello*|like",
            "sort" => "c"
        ]);

        try {
            $this->tabularData->list("bingo", $request);
            $this->fail("Should have thrown here");
        } catch (FieldNotFoundException $e){
            $this->assertEquals(new FieldNotFoundException("b", "column", "filtering"), $e);
        }

        $request->returnValue("getParameters", [
            "filter_test" => "1|gt",
            "filter_test" => "*hello*|like",
            "sort" => "c"
        ]);

        try {
            $this->tabularData->list("bingo", $request);
            $this->fail("Should have thrown here");
        } catch (FieldNotFoundException $e){
            $this->assertEquals(new FieldNotFoundException("c", "column", "sorting"), $e);
        }

    }

    public function testFilteredDeleteConvertsSimplifiedFilterSyntaxIntoFilterJunction() {

        // Simple equals filters
        $this->tabularData->filteredDelete("bingo", [["column" => "test", "value" => 3], ["column" => "test2", "value" => "Mark"]]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", 3), new Filter("[[test2]]", "Mark")])]));


        // In filters for arrays
        $this->tabularData->filteredDelete("bingo", [["column" => "test", "value" => [3, 5, 7]], ["column" => "test2", "value" => ["Mark", "Bob"]]]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", [3, 5, 7], FilterType::in), new Filter("[[test2]]", ["Mark", "Bob"], FilterType::in)])]));


        // Advanced filtering
        $this->tabularData->filteredDelete("bingo", [["column" => "test", "value" => "55", "matchType" => FilterType::gt], ["column" => "test2", "value" => "Mark", "matchType" => FilterType::like]]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", 55, FilterType::gt), new Filter("[[test2]]", "Mark", FilterType::like)])]));


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