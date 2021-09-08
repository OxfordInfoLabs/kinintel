<?php

namespace Kinintel\Services\Dashboard;

use Kiniauth\Objects\Account\Project;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSearchResult;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Alert\MatchRule\RowCountAlertMatchRuleConfiguration;
use Kinintel\ValueObjects\Dataset\TabularDataset;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php"

class DashboardServiceTest extends TestBase {


    /**
     * @var MockObject
     */
    private $datasetService;

    /**
     * @var DashboardService
     */
    private $dashboardService;


    /**
     * @var MetaDataService
     */
    private $metaDataService;


    public function setUp(): void {
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->metaDataService = MockObjectProvider::instance()->getMockInstance(MetaDataService::class);
        $this->dashboardService = new DashboardService($this->datasetService, $this->metaDataService);
    }


    public function testDashboardsAreValidatedOnSave() {

        $dashboard = new Dashboard("");

        try {
            $this->dashboardService->saveDashboard($dashboard);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }

        // Wrong datasource key for explicit instance
        $dashboard = new Dashboard("New Dashboard", [
            new DashboardDatasetInstance("example-1", null, "baddataset")
        ]);

        try {
            $this->dashboardService->saveDashboard($dashboard);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }


        // Bad dataset instance id
        $dashboard = new Dashboard("New Dashboard", [
            new DashboardDatasetInstance("example-1", 999)
        ]);

        try {
            $this->dashboardService->saveDashboard($dashboard);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }


    }

    public function testCanCreateRetrieveAndRemoveValidDashboard() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $dashboard = new DashboardSummary("Johnny 5");
        $id = $this->dashboardService->saveDashboard($dashboard, 1, 2);

        $reDashboard = $this->dashboardService->getDashboardById($id);
        $this->assertEquals("Johnny 5", $reDashboard->getTitle());


        $dashboard = new DashboardSummary("Test Instance", [
            new DashboardDatasetInstance("brandnew", null, "test-json", [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("value", "bingo")
                ]))
            ], [
                new Alert("rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 0)
                    ]), "There are no rows when there should be", 7)
            ])
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);

        $id = $this->dashboardService->saveDashboard($dashboard, 1, 2);

        $reDashboard = $this->dashboardService->getDashboardById($id);
        $this->assertEquals("Test Instance", $reDashboard->getTitle());
        $dashboardDatasetInstance = $reDashboard->getDatasetInstances()[0];
        $this->assertEquals("brandnew", $dashboardDatasetInstance->getInstanceKey());
        $this->assertEquals("test-json", $dashboardDatasetInstance->getDatasourceInstanceKey());
        $this->assertEquals([new TransformationInstance("filter", [
            "filters" => [[
                "fieldName" => "value",
                "value" => "bingo",
                "filterType" => "eq"
            ]],
            "logic" => "AND",
            "filterJunctions" => [],
            "sQLTransformationProcessorKey" => "filter"
        ])], $dashboardDatasetInstance->getTransformationInstances());


        $this->assertEquals(1, sizeof($dashboardDatasetInstance->getAlerts()));
        $firstAlert = $dashboardDatasetInstance->getAlerts()[0];
        $this->assertEquals("There are no rows when there should be", $firstAlert->getTemplate());
        $this->assertEquals("rowcount", $firstAlert->getMatchRuleType());
        $this->assertEquals(["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5], $firstAlert->getMatchRuleConfiguration());
        $this->assertEquals(new FilterTransformation([
            new Filter("score", 0)
        ]), $firstAlert->getFilterTransformation());
        $this->assertEquals(7, $firstAlert->getAlertGroupId());

        $this->dashboardService->removeDashboard($id);

        try {
            $this->dashboardService->getDashboardById($id);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanGetEvaluatedDatasetForValidDashboardDatasetInstanceUsingInstanceId() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $dataSetInstance = new DatasetInstance(new DatasetInstanceSummary("Test instance", "test-json"), 2);
        $dataSetInstance->save();

        // Save a dashboard
        $dashboard = new DashboardSummary("Test Instance", [
            new DashboardDatasetInstance("brandnew", $dataSetInstance->getId())
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);
        $id = $this->dashboardService->saveDashboard($dashboard, 1, 2);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformation = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById", $dataSet,
            [
                $dataSetInstance->getId(), [$transformation]
            ]);

        $evaluatedDataset = $this->dashboardService->getEvaluatedDataSetForDashboardDataSetInstance($id, "brandnew", [
            $transformation
        ]);
        $this->assertEquals($dataSet, $evaluatedDataset);

    }


    public function testCanGetEvaluatedDatasetForValidDashboardDatasetInstanceUsingExplicitDataSet() {

        $dashboardDataSetInstance = new DashboardDatasetInstance("otherset", null, "test-json", [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("value", "bingo")
            ]))
        ]);

        // Save a dashboard
        $dashboard = new DashboardSummary("Test Instance", [
            $dashboardDataSetInstance
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);
        $id = $this->dashboardService->saveDashboard($dashboard, 1, 2);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformation = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", $dataSet,
            [
                $dashboardDataSetInstance, [$transformation]
            ]);

        $evaluatedDataset = $this->dashboardService->getEvaluatedDataSetForDashboardDataSetInstance($id,
            "otherset", [$transformation]);
        $this->assertEquals($dataSet, $evaluatedDataset);

    }


    public function testCanSaveValidDashboardForProjectsAndTags() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $dashboardInstance = new DashboardSummary("Test Dataset");

        $tags = [new TagSummary("Project", "My Project", "project"),
            new TagSummary("Account2", "My Account", "account2")];

        $dashboardInstance->setTags($tags);


        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("Project", "My Project", "project"), 2, "soapSuds")),
            new ObjectTag(new Tag(new TagSummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            $tags, 2, "soapSuds"
        ]);

        $id = $this->dashboardService->saveDashboard($dashboardInstance, "soapSuds", 2);

        $dashboard = Dashboard::fetch($id);
        $this->assertEquals(2, $dashboard->getAccountId());
        $this->assertEquals("soapSuds", $dashboard->getProjectKey());

        $tags = $dashboard->getTags();
        $this->assertEquals(2, sizeof($tags));

        $this->assertEquals("account2", $tags[0]->getTag()->getKey());
        $this->assertEquals("project", $tags[1]->getTag()->getKey());


    }

    public function testCanGetFilteredDashboardsForAccountsOptionallyFilteredByProjectAndTag() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $accountDashboard = new DashboardSummary("Account Dashboard");
        $this->dashboardService->saveDashboard($accountDashboard, 1, 1);

        $accountDashboard = new DashboardSummary("Second Account Dashboard");
        $this->dashboardService->saveDashboard($accountDashboard, null, 1);


        $datasetProject = new Project("Dataset Project", 1, "datasetProject");
        $datasetProject->save();

        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("Special", "Special Tag", "special"), 1, "datasetProject")),
            new ObjectTag(new Tag(new TagSummary("General", "General Tag", "general"), 1, "datasetProject"))
        ], [
            [
                new TagSummary("Special", "", "special"),
                new TagSummary("General", "", "general")
            ], 1, "datasetProject"
        ]);

        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("General", "General Tag", "general"), 1, "datasetProject"))
        ], [
            [
                new TagSummary("General", "", "general")
            ], 1, "datasetProject"
        ]);


        $projectDashboard = new DashboardSummary("Project Dashboard");
        $projectDashboard->setTags([
            new TagSummary("Special", "", "special"),
            new TagSummary("General", "", "general")
        ]);
        $this->dashboardService->saveDashboard($projectDashboard, "datasetProject", 1);

        $projectDashboard = new DashboardSummary("Second Project Dashboard");
        $projectDashboard->setTags([
            new TagSummary("General", "", "general")
        ]);
        $this->dashboardService->saveDashboard($projectDashboard, "datasetProject", 1);


        $filtered = $this->dashboardService->filterDashboards("", [], null, 0, 10, 1);
        $this->assertEquals(4, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Project Dashboard", $filtered[1]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[2]);
        $this->assertEquals("Second Account Dashboard", $filtered[2]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[3]);
        $this->assertEquals("Second Project Dashboard", $filtered[3]->getTitle());


        // Filter on title
        $filtered = $this->dashboardService->filterDashboards("econd", [], null, 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Account Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dashboard", $filtered[1]->getTitle());


        // Filter on project key
        $filtered = $this->dashboardService->filterDashboards("", [], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dashboard", $filtered[1]->getTitle());

        // Filter on tags
        $filtered = $this->dashboardService->filterDashboards("", ["general"], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dashboard", $filtered[1]->getTitle());

        $filtered = $this->dashboardService->filterDashboards("", ["special"], "datasetProject", 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());


        // Offsets and limits
        $filtered = $this->dashboardService->filterDashboards("", ["general"], "datasetProject", 0, 1, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());


        $filtered = $this->dashboardService->filterDashboards("", ["general"], "datasetProject", 1, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Project Dashboard", $filtered[0]->getTitle());


    }

    public function testCanGetFilteredSharedDashboardsWithAccountIdOfNull() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $accountDashboard = new DashboardSummary("First Shared Dashboard");
        $this->dashboardService->saveDashboard($accountDashboard, 1, null);

        $accountDashboard = new DashboardSummary("Second Shared Dashboard");
        $this->dashboardService->saveDashboard($accountDashboard, null, null);

        // Log in as a person with projects and tags
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        $filtered = $this->dashboardService->filterDashboards("", [], null, 0, 10, null);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("First Shared Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Shared Dashboard", $filtered[1]->getTitle());

        AuthenticationHelper::login("admin@kinicart.com", "password");


    }


}