<?php

namespace Kinintel\Services\Datasource;


use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\ValueObjects\Query\FilterQuery;

include_once "autoloader.php";

class DatasourceQueryServiceTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var DatasourceQueryService
     */
    private $queryService;


    public function setUp(): void {
        $this->queryService = new DatasourceQueryService();
    }


    public function testQueryDataSourceWithSimpleQueryCallsApplyQueryOnDataSourceAndMaterialiseOnReturnedSource() {

        // Create two data sources for testing
        $mockDataSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $mockQueriedDataSource = MockObjectProvider::instance()->getMockInstance(Datasource::class);

        $mockDataset = MockObjectProvider::instance()->getMockInstance(Dataset::class);

        $query = new FilterQuery();

        $mockDataSource->returnValue("applyQuery", $mockQueriedDataSource, [
            $query
        ]);

        $mockQueriedDataSource->returnValue("materialise", $mockDataset);

        // Query
        $dataset = $this->queryService->queryDataSource($mockDataSource, $query);

        // Check we got the dataset back
        $this->assertEquals($mockDataset, $dataset);


    }


}