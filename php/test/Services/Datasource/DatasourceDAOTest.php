<?php

namespace Kinintel\Services\Datasource;

use Kiniauth\Objects\Account\PublicAccountSummary;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;

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
            "webservice", ["url" => "https://jsonplaceholder.typicode.com/users"],
            "http-basic", null, [], [], [], "My first test JSON Datasource"
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
        ], "http-basic", null, [], [], [], "Basic Datasource");
        $dataSourceInstance->save();

        $dataSourceInstance = new DatasourceInstance("db-sql", "Database SQL", "sqldatabase", [
            "source" => "table",
            "tableName" => "bob"
        ], "sql");
        $dataSourceInstance->save();


        // Check a couple of filters
        $filtered = $this->datasourceDAO->filterDatasourceInstances("json");
        $this->assertEquals(5, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON", "webservice", "Basic Datasource"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json", "Test JSON Datasource", "webservice", "My first test JSON Datasource"), $filtered[1]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds", "webservice"), $filtered[2]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-config", "Test JSON Datasource with Invalid Config", "webservice"), $filtered[3]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-creds", "Test JSON Datasource with invalid Creds", "webservice"), $filtered[4]);

        $filtered = $this->datasourceDAO->filterDatasourceInstances("database");
        $this->assertEquals(2, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON", "webservice", "Basic Datasource"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Database SQL", "sqldatabase"), $filtered[1]);

        // Check limiting and offset
        $filtered = $this->datasourceDAO->filterDatasourceInstances("json", 3);
        $this->assertEquals(3, sizeof($filtered));
        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON", "webservice", "Basic Datasource"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json", "Test JSON Datasource", "webservice", "My first test JSON Datasource"), $filtered[1]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds", "webservice"), $filtered[2]);


        $filtered = $this->datasourceDAO->filterDatasourceInstances("json", 10, 2);
        $this->assertEquals(3, sizeof($filtered));
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-explicit-creds", "Test JSON Datasource with Explicit Creds", "webservice"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-config", "Test JSON Datasource with Invalid Config", "webservice"), $filtered[1]);
        $this->assertEquals(new DatasourceInstanceSearchResult("test-json-invalid-creds", "Test JSON Datasource with invalid Creds", "webservice"), $filtered[2]);

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
        ], "sql");
        $dataSourceInstance->setAccountId(2);
        $dataSourceInstance->setProjectKey("soapSuds");
        $dataSourceInstance->save();


        // Check a couple of filters
        $filtered = $this->datasourceDAO->filterDatasourceInstances("", 10, 0, [], null, 2);
        $this->assertEquals(2, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON", "webservice"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Database SQL", "sqldatabase"), $filtered[1]);


        $filtered = $this->datasourceDAO->filterDatasourceInstances("", 10, 0, null, "soapSuds", 2);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Database SQL", "sqldatabase"), $filtered[0]);


    }


    public function testDatasourcesAreFilteredToIncludedTypesIfSupplied() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSourceInstance = new DatasourceInstance("db-json", "Database JSON", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance->setAccountId(2);
        $dataSourceInstance->save();

        $dataSourceInstance = new DatasourceInstance("db-sql", "Dataset Snapshot", "snapshot", [
            "source" => "table",
            "tableName" => "bob",
            "columns" => [
                new Field("id", "Id", null, Field::TYPE_STRING, true)
            ]
        ], "sql");
        $dataSourceInstance->setAccountId(2);
        $dataSourceInstance->save();


        // Check filtering
        $filtered = $this->datasourceDAO->filterDatasourceInstances("", 10, 0, ["webservice"], null, 2);
        $this->assertEquals(1, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON", "webservice"), $filtered[0]);


        // All returned if blank array supplied
        $filtered = $this->datasourceDAO->filterDatasourceInstances("", 10, 0, [], null, 2);
        $this->assertEquals(2, sizeof($filtered));

        $this->assertEquals(new DatasourceInstanceSearchResult("db-json", "Database JSON", "webservice"), $filtered[0]);
        $this->assertEquals(new DatasourceInstanceSearchResult("db-sql", "Dataset Snapshot", "snapshot"), $filtered[1]);


    }


    public function testCanGetDatasourceInstanceByTitleOptionallyLimitedToAccountAndProject() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSourceInstance1 = new DatasourceInstance("test-top-level", "Test Top Level", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance1->save();


        $dataSourceInstance2 = new DatasourceInstance("test-account-1", "Test Account", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance2->setAccountId(1);
        $dataSourceInstance2->setAccountSummary(PublicAccountSummary::fetch(1));
        $dataSourceInstance2->save();

        $dataSourceInstance3 = new DatasourceInstance("test-account-2", "Test Account", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance3->setAccountId(2);
        $dataSourceInstance3->setAccountSummary(PublicAccountSummary::fetch(2));
        $dataSourceInstance3->save();

        $dataSourceInstance4 = new DatasourceInstance("test-project-1", "Test Project", "sqldatabase", [
            "source" => "table",
            "tableName" => "bob"
        ], "sql");
        $dataSourceInstance4->setAccountId(2);
        $dataSourceInstance4->setAccountSummary(PublicAccountSummary::fetch(2));
        $dataSourceInstance4->setProjectKey("soapSuds");
        $dataSourceInstance4->save();

        $dataSourceInstance5 = new DatasourceInstance("test-project-2", "Test Project", "sqldatabase", [
            "source" => "table",
            "tableName" => "bob"
        ], "sql");
        $dataSourceInstance5->setAccountId(2);
        $dataSourceInstance5->setAccountSummary(PublicAccountSummary::fetch(2));
        $dataSourceInstance5->setProjectKey("wiperBlades");
        $dataSourceInstance5->save();


        $topLevel = $this->datasourceDAO->getDatasourceInstanceByTitle("Test Top Level");
        $this->assertEquals($dataSourceInstance1, $topLevel);

        $accountLevel1 = $this->datasourceDAO->getDatasourceInstanceByTitle("Test Account", null, 1);
        $this->assertEquals($dataSourceInstance2, $accountLevel1);

        $accountLevel2 = $this->datasourceDAO->getDatasourceInstanceByTitle("Test Account", null, 2);
        $this->assertEquals($dataSourceInstance3, $accountLevel2);

        $projectLevel1 = $this->datasourceDAO->getDatasourceInstanceByTitle("Test Project", "soapSuds", 2);
        $this->assertEquals($dataSourceInstance4, $projectLevel1);

        $projectLevel2 = $this->datasourceDAO->getDatasourceInstanceByTitle("Test Project", "wiperBlades", 2);
        $this->assertEquals($dataSourceInstance5, $projectLevel2);


    }


    public function testCanGetDatasourceInstanceByImportKeyOptionallyLimitedToAccountAndProject() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSourceInstance1 = new DatasourceInstance("test-top-level", "Test Top Level", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance1->setImportKey("import-top-level");
        $dataSourceInstance1->save();


        $dataSourceInstance2 = new DatasourceInstance("test-account-1", "Test Account", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance2->setAccountId(1);
        $dataSourceInstance2->setAccountSummary(PublicAccountSummary::fetch(1));
        $dataSourceInstance2->setImportKey("import-account");
        $dataSourceInstance2->save();

        $dataSourceInstance3 = new DatasourceInstance("test-account-2", "Test Account", "webservice", [
            "url" => "https://json-test.com/dbfeed"
        ], "http-basic");
        $dataSourceInstance3->setAccountId(2);
        $dataSourceInstance3->setAccountSummary(PublicAccountSummary::fetch(2));
        $dataSourceInstance3->setImportKey("import-account");
        $dataSourceInstance3->save();

        $dataSourceInstance4 = new DatasourceInstance("test-project-1", "Test Project", "sqldatabase", [
            "source" => "table",
            "tableName" => "bob"
        ], "sql");
        $dataSourceInstance4->setAccountId(2);
        $dataSourceInstance4->setAccountSummary(PublicAccountSummary::fetch(2));
        $dataSourceInstance4->setProjectKey("soapSuds");
        $dataSourceInstance4->setImportKey("import-project");
        $dataSourceInstance4->save();



        $topLevel = $this->datasourceDAO->getDatasourceInstanceByImportKey("import-top-level");
        $this->assertEquals($dataSourceInstance1, $topLevel);

        $accountLevel1 = $this->datasourceDAO->getDatasourceInstanceByImportKey("import-account",  1);
        $this->assertEquals($dataSourceInstance2, $accountLevel1);

        $accountLevel2 = $this->datasourceDAO->getDatasourceInstanceByImportKey("import-account",  2);
        $this->assertEquals($dataSourceInstance3, $accountLevel2);



    }



    public function testCanCheckWhetherImportKeyIsAvailableForDatasourceInstance() {

        // Create one from scratch - should be fine
        $datasourceInstance = new DatasourceInstance("existing-import", "Existing Import", "webservice", [
            "url" => "https://test.com"
        ]);
        $datasourceInstance->setAccountId(1);
        $datasourceInstance->setImportKey("existing-key");
        $datasourceInstance->save();


        // Now check for account duplicate
        $newInstance = new DatasourceInstance("new-import", "New Import", "webservice", [
            "url" => "https://test.com"
        ]);
        $newInstance->setAccountId(1);
        $this->assertFalse($this->datasourceDAO->importKeyAvailableForDatasourceInstance($newInstance, "existing-key"));

        // Now create a project one from scratch
        $datasourceInstance = new DatasourceInstance("existing-project", "Existing Import", "webservice", [
            "url" => "https://test.com"
        ]);
        $datasourceInstance->setAccountId(1);
        $datasourceInstance->setProjectKey("project1");
        $datasourceInstance->setImportKey("project-key");
        $datasourceInstance->save();

        // Now create an overlapping one
        $newInstance = new DatasourceInstance("new-project", "New Project key", "webservice", [
            "url" => "https://test.com"
        ]);
        $newInstance->setAccountId(1);
        $newInstance->setProjectKey("project1");

        $this->assertFalse($this->datasourceDAO->importKeyAvailableForDatasourceInstance($newInstance, "project-key"));


    }





}