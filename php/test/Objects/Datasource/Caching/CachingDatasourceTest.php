<?php

namespace Kinintel\Test\Objects\Datasource\Caching;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Caching\CachingDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class CachingDatasourceTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject
     */
    private $sourceDatasourceInstance;

    /**
     * @var MockObject
     */
    private $sourceDatasource;


    /**
     * @var MockObject
     */
    private $cacheDatasourceInstance;

    /**
     * @var MockObject
     */
    private $cacheDatasource;

    /**
     * @var MockObject
     */
    private $datasourceService;


    public function setUp(): void {

        $this->sourceDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $this->cacheDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);

        $this->sourceDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $this->cacheDatasource = MockObjectProvider::instance()->getMockInstance(BaseUpdatableDatasource::class);

        $this->sourceDatasourceInstance->returnValue("getTitle", "Source Datasource");
        $this->sourceDatasourceInstance->returnValue("returnDataSource", $this->sourceDatasource);

        $this->cacheDatasourceInstance->returnValue("getTitle", "Cache Data Source");
        $this->cacheDatasourceInstance->returnValue("returnDataSource", $this->cacheDatasource);

        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);

    }


    public function testIfSourceAndCachingDatasourcesSuppliedExplicitlyResultsCachedIfNotAlready() {

        $cachingDatasourceInstance = new DatasourceInstance("caching", "Caching Datasource", "caching",
            new CachingDatasourceConfig(null, $this->sourceDatasourceInstance,
                null, $this->cacheDatasourceInstance, 7));

        $sevenDaysAgo = (new \DateTime())->sub(new \DateInterval("P7D"));

        $encodedParams = json_encode(["param1" => "Joe", "param2" => "Bloggs"]);

        $this->cacheDatasource->returnValue("applyTransformation", $this->cacheDatasource,
            [
                new FilterTransformation([
                    new Filter("[[parameters]]", $encodedParams),
                    new Filter("[[cached_time]]", $sevenDaysAgo->format("Y-m-d H:i:s"), Filter::FILTER_TYPE_GREATER_THAN)])
            ]);

        $pagedDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $this->cacheDatasource->returnValue("applyTransformation", $pagedDatasource,
            [
                new PagingTransformation(1, 0)
            ]);

        // Produce no data from cache data source when paging applied
        $pagedDatasource->returnValue("materialise", new ArrayTabularDataset([], []));

        $expectedData = new ArrayTabularDataset([
            new Field("id"),
            new Field("data")
        ], [
            ["id" => 1, "data" => "Hello World"],
            ["id" => 2, "data" => "Goodbye Jeeves"],
            ["id" => 3, "data" => "Welcome Bobby"]
        ]);

        // Return some source data
        $this->sourceDatasource->returnValue("materialise", $expectedData, [
            ["param1" => "Joe", "param2" => "Bloggs"]
        ]);


        $enhancedData = new ArrayTabularDataset([
            new Field("parameters"),
            new Field("cached_time"),
            new Field("id"),
            new Field("data")
        ], [
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 1, "data" => "Hello World"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 2, "data" => "Goodbye Jeeves"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 3, "data" => "Welcome Bobby"]
        ]);

        $this->cacheDatasource->returnValue("materialise", $enhancedData);

        $dataSource = $cachingDatasourceInstance->returnDataSource();
        $results = $dataSource->materialise(["param1" => "Joe", "param2" => "Bloggs"]);


        // Check source data returned directly
        $this->assertEquals($enhancedData, $results);

        // Check augmented source data updated in cache data source
        $this->assertTrue($this->cacheDatasource->methodWasCalled("update",
            [$enhancedData]
        ));

    }


    public function testIfSourceAndCachingDatasourcesSuppliedExplicitlyResultsReturnedIfAlreadyCached() {

        $cachingDatasourceInstance = new DatasourceInstance("caching", "Caching Datasource", "caching",
            new CachingDatasourceConfig(null, $this->sourceDatasourceInstance,
                null, $this->cacheDatasourceInstance, 7));

        $sevenDaysAgo = (new \DateTime())->sub(new \DateInterval("P7D"));

        $encodedParams = json_encode(["param1" => "Joe", "param2" => "Bloggs"]);

        $this->cacheDatasource->returnValue("applyTransformation", $this->cacheDatasource,
            [
                new FilterTransformation([
                    new Filter("[[parameters]]", $encodedParams),
                    new Filter("[[cached_time]]", $sevenDaysAgo->format("Y-m-d H:i:s"), Filter::FILTER_TYPE_GREATER_THAN)])
            ]);

        $pagedDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $this->cacheDatasource->returnValue("applyTransformation", $pagedDatasource,
            [
                new PagingTransformation(1, 0)
            ]);

        $enhancedData = new ArrayTabularDataset([
            new Field("parameters"),
            new Field("cached_time"),
            new Field("id"),
            new Field("data")
        ], [
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 1, "data" => "Hello World"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 2, "data" => "Goodbye Jeeves"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 3, "data" => "Welcome Bobby"]
        ]);

        // Return data from cache
        $pagedDatasource->returnValue("materialise", $enhancedData);

        $this->cacheDatasource->returnValue("materialise", $enhancedData);

        $dataSource = $cachingDatasourceInstance->returnDataSource();
        $results = $dataSource->materialise(["param1" => "Joe", "param2" => "Bloggs"]);

        // Check source data returned directly
        $this->assertEquals($enhancedData, $results);

        // Check source not queried and update not performed
        $this->assertFalse($this->sourceDatasource->methodWasCalled("materialise"));
        $this->assertFalse($this->cacheDatasource->methodWasCalled("update"));

    }


    public function testIfSourceOrCachingDataSetsSuppliedAsKeysTheyAreLookedUpCorrectlyAndUsed() {

        $cachingDatasourceInstance = new DatasourceInstance("caching", "Caching Datasource", "caching",
            new CachingDatasourceConfig("testsource", null,
                "testcache", null, 5, 12));

        $cacheExpiry = (new \DateTime())->sub(new \DateInterval("P5D"))->sub(new \DateInterval("PT12H"));

        $encodedParams = json_encode(["param1" => "Joe", "param2" => "Bloggs"]);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $this->sourceDatasourceInstance, [
            "testsource"
        ]);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $this->cacheDatasourceInstance, [
            "testcache"
        ]);

        $this->cacheDatasource->returnValue("applyTransformation", $this->cacheDatasource,
            [
                new FilterTransformation([
                    new Filter("[[parameters]]", $encodedParams),
                    new Filter("[[cached_time]]", $cacheExpiry->format("Y-m-d H:i:s"), Filter::FILTER_TYPE_GREATER_THAN)])
            ]);

        $pagedDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $this->cacheDatasource->returnValue("applyTransformation", $pagedDatasource,
            [
                new PagingTransformation(1, 0)
            ]);

        $enhancedData = new ArrayTabularDataset([
            new Field("parameters"),
            new Field("cached_time"),
            new Field("id"),
            new Field("data")
        ], [
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 1, "data" => "Hello World"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 2, "data" => "Goodbye Jeeves"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 3, "data" => "Welcome Bobby"]
        ]);

        // Return data from cache
        $pagedDatasource->returnValue("materialise", $enhancedData);

        $this->cacheDatasource->returnValue("materialise", $enhancedData);

        $dataSource = $cachingDatasourceInstance->returnDataSource();
        $dataSource->setDatasourceService($this->datasourceService);
        $results = $dataSource->materialise(["param1" => "Joe", "param2" => "Bloggs"]);

        // Check source data returned directly
        $this->assertEquals($enhancedData, $results);

    }


    public function testIfFallbackToOlderConfigSetOlderResultsWillBeReturnedIfTheyExistOnBlankSourceResults() {

        $cachingDatasourceInstance = new DatasourceInstance("caching", "Caching Datasource", "caching",
            new CachingDatasourceConfig(null, $this->sourceDatasourceInstance,
                null, $this->cacheDatasourceInstance, 7, null, true));

        $sevenDaysAgo = (new \DateTime())->sub(new \DateInterval("P7D"));

        $encodedParams = json_encode(["param1" => "Joe", "param2" => "Bloggs"]);

        $this->cacheDatasource->returnValue("applyTransformation", $this->cacheDatasource,
            [
                new FilterTransformation([
                    new Filter("[[parameters]]", $encodedParams),
                    new Filter("[[cached_time]]", $sevenDaysAgo->format("Y-m-d H:i:s"), Filter::FILTER_TYPE_GREATER_THAN)])
            ]);

        $pagedDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $this->cacheDatasource->returnValue("applyTransformation", $pagedDatasource,
            [
                new PagingTransformation(1, 0)
            ]);

        // Produce no data from cache data source when paging applied
        $pagedDatasource->returnValue("materialise", new ArrayTabularDataset([], []));


        // Return no data from the source
        $expectedData = new ArrayTabularDataset([], []);
        $this->sourceDatasource->returnValue("materialise", $expectedData, [
            ["param1" => "Joe", "param2" => "Bloggs"]
        ]);


        $enhancedData = new ArrayTabularDataset([
            new Field("parameters"),
            new Field("cached_time"),
            new Field("id"),
            new Field("data")
        ], [
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 1, "data" => "Hello World"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 2, "data" => "Goodbye Jeeves"],
            ["parameters" => $encodedParams, "cached_time" => date("Y-m-d H:i:s"), "id" => 3, "data" => "Welcome Bobby"]
        ]);

        $previousCachedDatasource = MockObjectProvider::instance()->getMockInstance(Datasource::class);
        $previousCachedDatasource->returnValue("materialise", $enhancedData);

        // Look at previous entries (twice as long ago)
        $fourteenDaysAgo = (new \DateTime())->sub(new \DateInterval("P14D"));
        $this->cacheDatasource->returnValue("applyTransformation", $previousCachedDatasource, [
            new FilterTransformation([
                new Filter("[[parameters]]", $encodedParams),
                new Filter("[[cached_time]]", $fourteenDaysAgo->format("Y-m-d H:i:s"), Filter::FILTER_TYPE_GREATER_THAN)
            ])
        ]);

        // Execute and check we got enhanced data back
        $dataSource = $cachingDatasourceInstance->returnDataSource();
        $results = $dataSource->materialise(["param1" => "Joe", "param2" => "Bloggs"]);
        $this->assertEquals($enhancedData, $results);

    }


}