<?php

namespace Kinintel\Services\Datasource;

use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\TestBase;

include_once "autoloader.php";

class DatasourceDAOTest extends TestBase {


    /**
     * @var DatasourceDAO
     */
    private $datasourceDAO;


    /**
     * Set up
     */
    public function setUp(): void {
        $this->datasourceDAO = Container::instance()->get(DatasourceDAO::class);
    }


    public function testCanGetFileSystemDataSourceInstancesByKey() {

        // Creds Key
        $dataSource = $this->datasourceDAO->getDataSourceInstanceByKey("test-json");

        $this->assertEquals(new DatasourceInstance("test-json", "Test JSON Datasource",
            "webservice", ["url" => "https://test-json.com/feed"],
            "http-basic"
        ), $dataSource);


        // Explicit Creds
        $dataSource = $this->datasourceDAO->getDataSourceInstanceByKey("test-json-explicit-creds");

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

        $this->datasourceDAO->saveDataSourceInstance($dataSourceInstance);

        $reSource = $this->datasourceDAO->getDataSourceInstanceByKey("db-json");
        $this->assertEquals($dataSourceInstance, $reSource);

        $this->datasourceDAO->removeDataSourceInstance("db-json");

        try {
            $this->datasourceDAO->getDataSourceInstanceByKey("db-json");
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
        $filtered = $this->datasourceDAO->filterDatasourceInstances("json");
        $this->assertEquals(5, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json", "Test JSON Datasource"), $filtered[1]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds"), $filtered[2]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-config", "Test JSON Datasource with Invalid Config"), $filtered[3]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-creds", "Test JSON Datasource with invalid Creds"), $filtered[4]);

        $filtered = $this->datasourceDAO->filterDatasourceInstances("database");
        $this->assertEquals(2, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Database SQL"), $filtered[1]);

        // Check limiting and offset
        $filtered = $this->datasourceDAO->filterDatasourceInstances("json", 3);
        $this->assertEquals(3, sizeof($filtered));
        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json", "Test JSON Datasource"), $filtered[1]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds"), $filtered[2]);


        $filtered = $this->datasourceDAO->filterDatasourceInstances("json", 10, 2);
        $this->assertEquals(3, sizeof($filtered));
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-config", "Test JSON Datasource with Invalid Config"), $filtered[1]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-creds", "Test JSON Datasource with invalid Creds"), $filtered[2]);

    }


    public function testCanFilterDatasourcesByAccountIdAndProjectKey() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSourceInstance = new DatasourceInstance("db-json", "Database JSON", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance->setAccountId(2);
        $dataSourceInstance->save();

        $dataSourceInstance = new DatasourceInstance("db-sql", "Database SQL", "sqldatabase", [
            "source" => "table",
            "tableName" => "bob"
        ], "http-basic");
        $dataSourceInstance->setAccountId(2);
        $dataSourceInstance->setProjectKey("soapSuds");
        $dataSourceInstance->save();


        // Check a couple of filters
        $filtered = $this->datasourceDAO->filterDatasourceInstances("", 10, 0, null, 2);
        $this->assertEquals(2, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Database SQL"), $filtered[1]);


        $filtered = $this->datasourceDAO->filterDatasourceInstances("", 10, 0, "soapSuds", 2);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Database SQL"), $filtered[0]);


    }


    public function testDatasetSnapshotsAreIgnoredInFilteredResults() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSourceInstance = new DatasourceInstance("db-json", "Database JSON", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance->setAccountId(2);
        $dataSourceInstance->save();

        $dataSourceInstance = new DatasourceInstance("db-sql", "Dataset Snapshot", "snapshot", [
            "source" => "table",
            "tableName" => "bob"
        ], "http-basic");
        $dataSourceInstance->setAccountId(2);
        $dataSourceInstance->save();


        // Check a couple of filters
        $filtered = $this->datasourceDAO->filterDatasourceInstances("", 10, 0, null, 2);
        $this->assertEquals(1, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON"), $filtered[0]);


    }

}