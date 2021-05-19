<?php


namespace Kinintel\Services\Datasource;

use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
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


    public function testCanStoreAndRetrieveAndRemoveTopLevelDatabaseDataSourceInstances() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSourceInstance = new DatasourceInstance("db-json", "Database JSON", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");

        $this->dataSourceService->saveDataSourceInstance($dataSourceInstance);

        $reSource = $this->dataSourceService->getDataSourceInstanceByKey("db-json");
        $this->assertEquals($dataSourceInstance, $reSource);

        $this->dataSourceService->removeDataSourceInstance("db-json");

        try {
            $this->dataSourceService->getDataSourceInstanceByKey("db-json");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // Success
        }
    }


    public function testCanGetFilteredDatasourcesUsingBothDatabaseAndFilesystem() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSourceInstance = new DatasourceInstance("db-json", "Database JSON", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance->save();

        $dataSourceInstance = new DatasourceInstance("db-sql", "Database SQL", "sqldatabase", [
            "source" => "table",
            "tableName" => "bob"
        ], "http-basic");
        $dataSourceInstance->save();


        // Check a couple of filters
        $filtered = $this->dataSourceService->filterDatasourceInstances("json");
        $this->assertEquals(5, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json", "Test JSON Datasource"), $filtered[1]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds"), $filtered[2]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-config", "Test JSON Datasource with Invalid Config"), $filtered[3]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-creds", "Test JSON Datasource with invalid Creds"), $filtered[4]);

        $filtered = $this->dataSourceService->filterDatasourceInstances("database");
        $this->assertEquals(2, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Database SQL"), $filtered[1]);

    }


}