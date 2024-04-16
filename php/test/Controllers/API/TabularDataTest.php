<?php

namespace Kinintel\Test\Controllers\API;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Controllers\API\TabularData;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;

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


    public function setUp(): void {

        parent::setUp();

        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->tabularData = new TabularData($this->datasourceService);

        $datasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $datasource = new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig("table", "test_data", "", [new Field("test"), new Field("test2")]),null,null);
        $datasourceInstance->returnValue("returnDataSource", $datasource);

        $this->datasourceService->returnValue("getDataSourceInstanceByImportKey", $datasourceInstance, ["bingo"]);
    }

    public function testFilteredDeleteConvertsSimplifiedFilterSyntaxIntoFilterJunction() {

        // Simple equals filters
        $this->tabularData->filteredDelete("bingo", ["test" => 3, "test2" => "Mark"]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", 3), new Filter("[[test2]]", "Mark")])]));


        // In filters for arrays
        $this->tabularData->filteredDelete("bingo", ["test" => [3, 5, 7], "test2" => ["Mark", "Bob"]]);
        $this->assertTrue($this->datasourceService->methodWasCalled("filteredDeleteFromDatasourceInstanceByImportKey", ["bingo",
            new FilterJunction([new Filter("[[test]]", [3, 5, 7], Filter::FILTER_TYPE_IN), new Filter("[[test2]]", ["Mark", "Bob"], Filter::FILTER_TYPE_IN)])]));


        // Advanced filtering
        $this->tabularData->filteredDelete("bingo", ["test" => ["value" => "55", "matchType" => Filter::FILTER_TYPE_GREATER_THAN], "test2" => ["value" => "Mark", "matchType" => Filter::FILTER_TYPE_LIKE]]);
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