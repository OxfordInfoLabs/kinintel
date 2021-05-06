<?php


namespace Kinintel\Services\Datasource;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceDataSourceConfig;

include_once "autoloader.php";

class DatasourceServiceTest extends TestBase {

    /**
     * @var DatasourceService
     */
    private $dataSourceService;


    /**
     * Set up
     */
    public function setUp(): void {
        $this->dataSourceService = Container::instance()->get(DatasourceService::class);
    }

    public function testCanGetFileSystemDataSourceInstancesByKey() {

        // Creds Key
        $dataSource = $this->dataSourceService->getDataSourceInstanceByKey("test-json");

        $this->assertEquals(new DatasourceInstance("test-json", "Test JSON Datasource",
            "webservice", ["url" => "https://test-json.com/feed"],
            "http-basic"
        ), $dataSource);


        // Explicit Creds
        $dataSource = $this->dataSourceService->getDataSourceInstanceByKey("test-json-explicit-creds");

        $this->assertEquals(new DatasourceInstance("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds",
            "webservice", ["url" => "https://test-json.com/feed"],
            null, "http-basic", [
                "username" => "mark",
                "password" => "test"
            ]
        ), $dataSource);


    }


    public function testCanStoreAndRetrieveDatabaseDataSourceInstances() {

        $dataSourceInstance = new DatasourceInstance("db-json", "Database JSON", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");

        $this->dataSourceService->saveDataSourceInstance($dataSourceInstance);

        $reSource = $this->dataSourceService->getDataSourceInstanceByKey("db-json");
        $this->assertEquals($dataSourceInstance, $reSource);


    }





}