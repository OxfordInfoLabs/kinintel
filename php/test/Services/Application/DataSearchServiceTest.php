<?php

namespace Kinintel\Test\Services\Application;

use Google\Service\Datastore\Sum;
use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Query\SummarisedValue;
use Kinintel\Objects\Application\DataSearch;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Application\DataSearchService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Application\DataSearchItem;
use Kinintel\ValueObjects\DataProcessor\Configuration\DataProcessorAction;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class DataSearchServiceTest extends TestBase {

    /**
     * @var DataSearchService
     */
    private $service;

    public function setUp(): void {
        $this->service = Container::instance()->get(DataSearchService::class);

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $db = Container::instance()->get(DatabaseConnection::class);
        $db->query("DELETE FROM ki_dataset_instance WHERE account_id IS NULL");
        $db->query("DELETE FROM ki_datasource_instance WHERE account_id IS NULL");


        // Account only dataset
        $dataset1 = new DatasetInstance(new DatasetInstanceSummary("Search Dataset 1", "test-json", null, [], [], [], "A search dataset 1"), 6);
        $dataset1->save();

        // Other account dataset
        $dataset2 = new DatasetInstance(new DatasetInstanceSummary("Search Dataset 2", "test-json", null, [], [], [], "A search dataset 2"), 7);
        $dataset2->save();

        // Project dataset
        $dataset3 = new DatasetInstance(new DatasetInstanceSummary("Search Dataset 3", "test-json", null, [], [], [], "A search dataset 3"), 6, "testProj");
        $dataset3->save();

        // Shared dataset
        $dataset4 = new DatasetInstance(new DatasetInstanceSummary("Search Shared Dataset 1", "test-json", null, [], [], [], "A search shared dataset 1"), 7);
        $dataset4->save();
        (new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 6, "test", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset4->getId()))->save();

        // Global dataset
        $dataset5 = new DatasetInstance(new DatasetInstanceSummary("Global Dataset 1", "test-json", null, [], [], [], "A global dataset 1"), null);
        $dataset5->save();


        // Custom data source (account scoped)
        $datasource1 = new DatasourceInstance("custom-search-datasource-1", "Custom Search Datasource 1", "custom", ["tableName" => "mysearchtest1", "columns" => [new Field("id")]], "test");
        $datasource1->setAccountId(6);
        $datasource1->save();

        // Other account
        $datasource2 = new DatasourceInstance("custom-search-datasource-2", "Custom Search Datasource 2", "custom", ["tableName" => "mysearchtest1", "columns" => [new Field("id")]], "test");
        $datasource2->setAccountId(7);
        $datasource2->save();

        // Project datasource
        $datasource3 = new DatasourceInstance("custom-search-datasource-3", "Custom Search Datasource 3", "custom", ["tableName" => "mysearchtest1", "columns" => [new Field("id")]], "test");
        $datasource3->setAccountId(6);
        $datasource3->setProjectKey("testProj");
        $datasource3->save();


        // Account Snapshot
        $snapshot1 = new DataProcessorInstance("snapshot-search-1", "Snapshot Search 1", "tabulardatasetsnapshot");
        $snapshot1->setAccountId(6);
        $snapshot1->save();

        // Other account snapshot
        $snapshot2 = new DataProcessorInstance("snapshot-search-2", "Snapshot Search 2", "tabulardatasetincrementalsnapshot");
        $snapshot2->setAccountId(7);
        $snapshot2->save();

        // Project snapshot
        $snapshot2 = new DataProcessorInstance("snapshot-search-3", "Snapshot Search 3", "tabulardatasetincrementalsnapshot");
        $snapshot2->setAccountId(6);
        $snapshot2->setProjectKey("testProj");
        $snapshot2->save();


        // Other processor
        $processor1 = new DataProcessorInstance("processor-search-1", "Processor Search 1", "querycaching", ["sourceQueryId" => 12]);
        $processor1->setAccountId(6);
        $processor1->save();

        // Other account processor
        $processor2 = new DataProcessorInstance("processor-search-2", "Processor Search 2", "querycaching", ["sourceQueryId" => 12]);
        $processor2->setAccountId(7);
        $processor2->save();

        // Project processor
        $processor2 = new DataProcessorInstance("processor-search-3", "Processor Search 3", "querycaching", ["sourceQueryId" => 12]);
        $processor2->setAccountId(6);
        $processor2->setProjectKey("testProj");
        $processor2->save();


    }


    public function testProjectAndAccountOwnedDataItemsAreCorrectlyIncludedInAccountSearches() {

        // Query for all records as account 1 (non project)
        $account1Records = $this->service->searchForAccountDataItems([], 100, 0, null, 6);


        $this->assertEquals(6, sizeof($account1Records));
        $this->assertEquals(new DataSearchItem("custom", "custom-search-datasource-1", "Custom Search Datasource 1", "",
            [new DataProcessorAction("Select", "custom-search-datasource-1")]), $account1Records[0]);
        $this->assertEquals(new DataSearchItem("globaldataset", $dataset5->getId(), "Global Dataset 1", "A global dataset 1",
            [new DataProcessorAction("Select", null, $dataset5->getId())]), $account1Records[1]);
        $this->assertEquals(new DataSearchItem("querycaching", "processor-search-1", "Processor Search 1", "",
            [new DataProcessorAction("Latest", "processor-search-1_caching"),
                new DataProcessorAction("Historical Entries", "processor-search-1_cache")]), $account1Records[2]);
        $this->assertEquals(new DataSearchItem("dataset", $dataset1->getId(), "Search Dataset 1", "A search dataset 1",
            [new DataProcessorAction("Select", null, $dataset1->getId())]), $account1Records[3]);
        $this->assertEquals(new DataSearchItem("shareddataset", $dataset4->getId(), "Search Shared Dataset 1", "A search shared dataset 1",
            [new DataProcessorAction("Select", null, $dataset4->getId())]), $account1Records[4]);
        $this->assertEquals(new DataSearchItem("snapshot", "snapshot-search-1", "Snapshot Search 1", "",
            [new DataProcessorAction("Latest", "snapshot-search-1_latest"),
                new DataProcessorAction("Historical Entries", "snapshot-search-1")]), $account1Records[5]);


        // Query for all records as account 1 testProject
        $account1Records = $this->service->searchForAccountDataItems([], 100, 0, "testProj", 6);

        $this->assertEquals(10, sizeof($account1Records));
        $this->assertEquals(new DataSearchItem("custom", "custom-search-datasource-1", "Custom Search Datasource 1", "",
            [new DataProcessorAction("Select", "custom-search-datasource-1")]), $account1Records[0]);
        $this->assertEquals(new DataSearchItem("custom", "custom-search-datasource-3", "Custom Search Datasource 3", "",
            [new DataProcessorAction("Select", "custom-search-datasource-3")]), $account1Records[1]);
        $this->assertEquals(new DataSearchItem("globaldataset", $dataset5->getId(), "Global Dataset 1", "A global dataset 1",
            [new DataProcessorAction("Select", null, $dataset5->getId())]), $account1Records[2]);
        $this->assertEquals(new DataSearchItem("querycaching", "processor-search-1", "Processor Search 1", "",
            [new DataProcessorAction("Latest", "processor-search-1_caching"),
                new DataProcessorAction("Historical Entries", "processor-search-1_cache")]), $account1Records[3]);
        $this->assertEquals(new DataSearchItem("querycaching", "processor-search-3", "Processor Search 3", "",
            [new DataProcessorAction("Latest", "processor-search-3_caching"),
                new DataProcessorAction("Historical Entries", "processor-search-3_cache")]), $account1Records[4]);

        $this->assertEquals(new DataSearchItem("dataset", $dataset1->getId(), "Search Dataset 1", "A search dataset 1",
            [new DataProcessorAction("Select", null, $dataset1->getId())]), $account1Records[5]);

        $this->assertEquals(new DataSearchItem("dataset", $dataset3->getId(), "Search Dataset 3", "A search dataset 3",
            [new DataProcessorAction("Select", null, $dataset3->getId())]), $account1Records[6]);


        $this->assertEquals(new DataSearchItem("shareddataset", $dataset4->getId(), "Search Shared Dataset 1", "A search shared dataset 1",
            [new DataProcessorAction("Select", null, $dataset4->getId())]), $account1Records[7]);
        $this->assertEquals(new DataSearchItem("snapshot", "snapshot-search-1", "Snapshot Search 1", "",
            [new DataProcessorAction("Latest", "snapshot-search-1_latest"),
                new DataProcessorAction("Historical Entries", "snapshot-search-1")]), $account1Records[8]);
        $this->assertEquals(new DataSearchItem("snapshot", "snapshot-search-3", "Snapshot Search 3", "",
            [new DataProcessorAction("Select", "snapshot-search-3")]), $account1Records[9]);


        // Now check we can filter these
        $snapshotFiltered = $this->service->searchForAccountDataItems(["search" => "snapshot"], 100, 0, "testProj", 6);
        $this->assertEquals(2, sizeof($snapshotFiltered));
        $this->assertEquals(new DataSearchItem("snapshot", "snapshot-search-1", "Snapshot Search 1", "",
            [new DataProcessorAction("Latest", "snapshot-search-1_latest"),
                new DataProcessorAction("Historical Entries", "snapshot-search-1")]), $snapshotFiltered[0]);
        $this->assertEquals(new DataSearchItem("snapshot", "snapshot-search-3", "Snapshot Search 3", "",
            [new DataProcessorAction("Select", "snapshot-search-3")]), $snapshotFiltered[1]);


        // Type based filtering
        $typeFiltered = $this->service->searchForAccountDataItems(["type" => "querycaching"], 100, 0, "testProj", 6);
        $this->assertEquals(2, sizeof($typeFiltered));

        $this->assertEquals(new DataSearchItem("querycaching", "processor-search-1", "Processor Search 1", "",
            [new DataProcessorAction("Latest", "processor-search-1_caching"),
                new DataProcessorAction("Historical Entries", "processor-search-1_cache")]), $typeFiltered[0]);
        $this->assertEquals(new DataSearchItem("querycaching", "processor-search-3", "Processor Search 3", "",
            [new DataProcessorAction("Latest", "processor-search-3_caching"),
                new DataProcessorAction("Historical Entries", "processor-search-3_cache")]), $typeFiltered[1]);


        // Offset and limit
        $account1Records = $this->service->searchForAccountDataItems([], 2, 0, "testProj", 6);
        $this->assertEquals(2, sizeof($account1Records));
        $this->assertEquals(new DataSearchItem("custom", "custom-search-datasource-1", "Custom Search Datasource 1", "",
            [new DataProcessorAction("Select", "custom-search-datasource-1")]), $account1Records[0]);
        $this->assertEquals(new DataSearchItem("custom", "custom-search-datasource-3", "Custom Search Datasource 3", "",
            [new DataProcessorAction("Select", "custom-search-datasource-3")]), $account1Records[1]);

        $account1Records = $this->service->searchForAccountDataItems([], 100, 8, "testProj", 6);
        $this->assertEquals(2, sizeof($account1Records));
        $this->assertEquals(new DataSearchItem("snapshot", "snapshot-search-1", "Snapshot Search 1", "",
            [new DataProcessorAction("Latest", "snapshot-search-1_latest"),
                new DataProcessorAction("Historical Entries", "snapshot-search-1")]), $account1Records[0]);
        $this->assertEquals(new DataSearchItem("snapshot", "snapshot-search-3", "Snapshot Search 3", "",
            [new DataProcessorAction("Select", "snapshot-search-3")]), $account1Records[1]);


    }


    public function testCanGetTypesForSearchTerm() {

        // Account one
        $types = $this->service->getMatchingAccountDataItemTypesForSearchTerm("", null, 6);

        $this->assertEquals([
            new SummarisedValue("custom", 1),
            new SummarisedValue("dataset", 1),
            new SummarisedValue("globaldataset", 1),
            new SummarisedValue("querycaching", 1),
            new SummarisedValue("shareddataset", 1),
            new SummarisedValue("snapshot", 1)
        ], $types);


        // Project one
        $types = $this->service->getMatchingAccountDataItemTypesForSearchTerm("", "testProj", 6);

        $this->assertEquals([
            new SummarisedValue("custom", 2),
            new SummarisedValue("dataset", 2),
            new SummarisedValue("globaldataset", 1),
            new SummarisedValue("querycaching", 2),
            new SummarisedValue("shareddataset", 1),
            new SummarisedValue("snapshot", 2)
        ], $types);

        // Filtered
        $types = $this->service->getMatchingAccountDataItemTypesForSearchTerm("snapshot", null, 6);
        $this->assertEquals([new SummarisedValue("snapshot", 1)], $types);

        $types = $this->service->getMatchingAccountDataItemTypesForSearchTerm("dataset", null, 6);
        $this->assertEquals([
            new SummarisedValue("dataset", 1),
            new SummarisedValue("globaldataset", 1),
            new SummarisedValue("shareddataset", 1)], $types);


    }

}