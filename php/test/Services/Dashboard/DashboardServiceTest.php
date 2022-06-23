<?php

namespace Kinintel\Services\Dashboard;

use Kiniauth\Objects\Account\Project;
use Kiniauth\Objects\MetaData\Category;
use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\ObjectCategory;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
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
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Alert\ActiveDashboardDatasetAlerts;
use Kinintel\ValueObjects\Alert\MatchRule\RowCountAlertMatchRuleConfiguration;
use Kinintel\ValueObjects\Dataset\TabularDataset;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

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
        $this->dashboardService = new DashboardService($this->datasetService, $this->metaDataService, Container::instance()->get(SecurityService::class));
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


        $categories = [
            new CategorySummary("Account2", "An account wide category available to account 2", "account2")
        ];

        $dashboard = new DashboardSummary("Test Instance", [
            new DashboardDatasetInstance("brandnew", null, "test-json", [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("value", "bingo")
                ]))
            ], [
                new Alert("Bingo", "rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 0)
                    ]), "There are no rows when there should be", "No rows", 7)
            ])
        ], [
            "color" => "green",
            "font" => "Arial"
        ], null, null, "My Brand new Test Dashboard", "A longer description of my brand new test dashboard",
            $categories);

        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            $categories, 2, "soapSuds"
        ]);

        $id = $this->dashboardService->saveDashboard($dashboard, "soapSuds", 2);

        $reDashboard = $this->dashboardService->getDashboardById($id);
        $this->assertEquals("Test Instance", $reDashboard->getTitle());
        $this->assertEquals("My Brand new Test Dashboard", $reDashboard->getSummary());
        $this->assertEquals("A longer description of my brand new test dashboard", $reDashboard->getDescription());
        $this->assertEquals($categories, $reDashboard->getCategories());

        $dashboardDatasetInstance = $reDashboard->getDatasetInstances()[0];
        $this->assertEquals("brandnew", $dashboardDatasetInstance->getInstanceKey());
        $this->assertEquals("test-json", $dashboardDatasetInstance->getDatasourceInstanceKey());
        $this->assertEquals([new TransformationInstance("filter", [
            "filters" => [[
                "lhsExpression" => "value",
                "rhsExpression" => "bingo",
                "filterType" => "eq"
            ]],
            "logic" => "AND",
            "filterJunctions" => [],
            "sQLTransformationProcessorKey" => "filter"
        ])], $dashboardDatasetInstance->getTransformationInstances());


        $this->assertEquals(1, sizeof($dashboardDatasetInstance->getAlerts()));
        $firstAlert = $dashboardDatasetInstance->getAlerts()[0];
        $this->assertEquals("There are no rows when there should be", $firstAlert->getNotificationTemplate());
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



    public function testCanGetFilteredDashboardsForAccountsOptionallyFilteredByProjectTagAndCategories() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $categories = [
            new CategorySummary("Account1", "An account wide category available to account 1", "account1")
        ];
        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 1", "Account 1", "account1"), 1)),
        ], [
            $categories, 1, "soapSuds"
        ]);

        $accountDashboard = new DashboardSummary("Account Dashboard", [], null, null, null, "Account Dashboard Summary", "Account Dashboard Description", $categories);
        $this->dashboardService->saveDashboard($accountDashboard, "soapSuds", 1);

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


        $filtered = $this->dashboardService->filterDashboards("", [], [], null, 0, 10, 1);
        $this->assertEquals(6, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dashboard", $filtered[0]->getTitle());
        $this->assertEquals("Account Dashboard Summary", $filtered[0]->getSummary());
        $this->assertEquals("Account Dashboard Description", $filtered[0]->getDescription());
        $this->assertEquals($categories, $filtered[0]->getCategories());


        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Project Dashboard", $filtered[1]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[2]);
        $this->assertEquals("Second Account Dashboard", $filtered[2]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[3]);
        $this->assertEquals("Second Project Dashboard", $filtered[3]->getTitle());


        // Filter on title
        $filtered = $this->dashboardService->filterDashboards("econd", [], [], null, 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Account Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dashboard", $filtered[1]->getTitle());


        // Filter on categories
        $filtered = $this->dashboardService->filterDashboards("", ["account1"], [], null, 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertEquals("Account Dashboard", $filtered[0]->getTitle());

        // Filter on project key
        $filtered = $this->dashboardService->filterDashboards("", [], [], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dashboard", $filtered[1]->getTitle());

        // Filter on tags
        $filtered = $this->dashboardService->filterDashboards("", [], ["general"], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dashboard", $filtered[1]->getTitle());

        $filtered = $this->dashboardService->filterDashboards("", [], ["special"], "datasetProject", 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());

        // Filter on special NONE tags
        $filtered = $this->dashboardService->filterDashboards("", [], ["NONE"], null, 0, 10, 1);
        $this->assertEquals(4, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Account Dashboard", $filtered[1]->getTitle());


        // Offsets and limits
        $filtered = $this->dashboardService->filterDashboards("", [], ["general"], "datasetProject", 0, 1, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dashboard", $filtered[0]->getTitle());


        $filtered = $this->dashboardService->filterDashboards("", [], ["general"], "datasetProject", 1, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Project Dashboard", $filtered[0]->getTitle());


    }


    public function testCanGetAllDashboardsForAccountOrProject() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $all = $this->dashboardService->getAllDashboards(null, 1);
        $this->assertEquals(6, sizeof($all));
        $this->assertInstanceOf(DashboardSummary::class, $all[0]);
        $this->assertEquals("Account Dashboard", $all[0]->getTitle());
        $this->assertEquals("Account Dashboard Summary", $all[0]->getSummary());
        $this->assertEquals("Account Dashboard Description", $all[0]->getDescription());


        $this->assertInstanceOf(DashboardSummary::class, $all[1]);
        $this->assertEquals("Project Dashboard", $all[1]->getTitle());
        $this->assertInstanceOf(DashboardSummary::class, $all[2]);
        $this->assertEquals("Second Account Dashboard", $all[2]->getTitle());
        $this->assertInstanceOf(DashboardSummary::class, $all[3]);
        $this->assertEquals("Second Project Dashboard", $all[3]->getTitle());


        // Filter on project key
        $all = $this->dashboardService->getAllDashboards("datasetProject", 1);
        $this->assertEquals(2, sizeof($all));
        $this->assertInstanceOf(DashboardSummary::class, $all[0]);
        $this->assertEquals("Project Dashboard", $all[0]->getTitle());
        $this->assertInstanceOf(DashboardSummary::class, $all[1]);
        $this->assertEquals("Second Project Dashboard", $all[1]->getTitle());

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


        $filtered = $this->dashboardService->filterDashboards("", [], [], null, 0, 10, null);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[0]);
        $this->assertEquals("First Shared Dashboard", $filtered[0]->getTitle());
        $this->assertInstanceOf(DashboardSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Shared Dashboard", $filtered[1]->getTitle());

        AuthenticationHelper::login("admin@kinicart.com", "password");


    }


    public function testCanGetInUseDashboardCategories() {

        $accountCategories = [
            new CategorySummary("Account2", "An account wide category available to account 2", "account2")
        ];

        $projectCategories = [
            new CategorySummary("Project", "A project level category available to just one project", "project"),
        ];


        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            $accountCategories, 2, null
        ]);

        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Project", "Project", "project"), 2, "soapSuds")),
        ], [
            $projectCategories, 2, "soapSuds"
        ]);


        $accountDashboard = new DashboardSummary("Account Dashboard", [], null, null, null, "Account Dashboard Summary", "Account Dashboard Description", $accountCategories);
        $this->dashboardService->saveDashboard($accountDashboard, null, 2);

        $projectDashboard = new DashboardSummary("Project Dashboard", [], null, null, null, "Project Dashboard Summary", "Project Dashboard Description", $projectCategories);
        $this->dashboardService->saveDashboard($projectDashboard, "soapSuds", 2);

        $this->metaDataService->returnValue("getMultipleCategoriesByKey", array_merge($accountCategories, $projectCategories), [
            ["", "account2", "project"], null, 2
        ]);

        $this->metaDataService->returnValue("getMultipleCategoriesByKey", $projectCategories, [
            ["", "project"], "soapSuds", 2
        ]);


        $this->assertEquals(array_merge($accountCategories, $projectCategories), $this->dashboardService->getInUseDashboardCategories([], null, 2));
        $this->assertEquals($projectCategories, $this->dashboardService->getInUseDashboardCategories([], "soapSuds", 2));

    }


    public function testCanGetActiveDashboardDatasetAlertsMatchDatasetAlertsWithAlertGroup() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dashboard1 = new DashboardSummary("Test Instance", [
            new DashboardDatasetInstance("brandnew", null, "test-json", [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("value", "bingo")
                ]))
            ], [
                new Alert("New Alert", "rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 0)
                    ]), "There are no rows when there should be", "No Rows", 1),
                new Alert("Second Alert", "rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 1)
                    ]), "There are rows when there should be none", "Rows", 2),

            ])
        ], [
            "color" => "green",
            "font" => "Arial"
        ], null, true);

        $dashboard1Id = $this->dashboardService->saveDashboard($dashboard1, 1, 2);


        $dashboard2 = new DashboardSummary("Test Instance 2", [
            new DashboardDatasetInstance("brandnew", null, "test-json", [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("value", "bingo")
                ]))
            ], [
                new Alert("Third Alert", "rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 0)
                    ]), "There are no rows when there should be", "No Rows", 2),
                new Alert("Fourth Alert", "rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 1)
                    ]), "There are rows when there should be none", "Rows", 2),

            ])
        ], [
            "color" => "green",
            "font" => "Arial"
        ], null, true);

        $dashboard2Id = $this->dashboardService->saveDashboard($dashboard2, 1, 2);


        /**
         * @var Dashboard $dashboard1
         */
        $dashboard1 = Dashboard::fetch($dashboard1Id);

        /**
         * @var Dashboard $dashboard2
         */
        $dashboard2 = Dashboard::fetch($dashboard2Id);


        $matchingActiveAlerts = $this->dashboardService->getActiveDashboardDatasetAlertsMatchingAlertGroup(1);


        $this->assertEquals([new ActiveDashboardDatasetAlerts($dashboard1->getDatasetInstances()[0],
            [$dashboard1->getDatasetInstances()[0]->getAlerts()[0]])], $matchingActiveAlerts);

        $matchingActiveAlerts = $this->dashboardService->getActiveDashboardDatasetAlertsMatchingAlertGroup(2);
        $this->assertEquals([new ActiveDashboardDatasetAlerts($dashboard1->getDatasetInstances()[0],
            [$dashboard1->getDatasetInstances()[0]->getAlerts()[1]]),
            new ActiveDashboardDatasetAlerts($dashboard2->getDatasetInstances()[0],
                $dashboard2->getDatasetInstances()[0]->getAlerts())], $matchingActiveAlerts);


    }


    public function testCanReturnCopyOfDashboard() {


        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dashboard1 = new DashboardSummary("Test Instance", [
            new DashboardDatasetInstance("brandnew", null, "test-json", [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("value", "bingo")
                ]))
            ], [
                new Alert("First Alert", "rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 0)
                    ]), "There are no rows when there should be", 1),
                new Alert("Second Alert", "rowcount", ["matchType" => RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS, "value" => 5],
                    new FilterTransformation([
                        new Filter("score", 1)
                    ]), "There are rows when there should be none", 2),

            ])
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);

        $dashboard1Id = $this->dashboardService->saveDashboard($dashboard1, null, null);

        // Log in as a regular account
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Check copy rules
        $dashboard = $this->dashboardService->copyDashboard($dashboard1Id);
        $this->assertNull($dashboard->getId());
        $this->assertEquals(1, sizeof($dashboard->getDatasetInstances()));
        $this->assertEquals("brandnew", $dashboard->getDatasetInstances()[0]->getInstanceKey());
        $this->assertFalse($dashboard->isReadOnly());
        $this->assertEquals(2, sizeof($dashboard->getDatasetInstances()[0]->getAlerts()));
        $this->assertNull($dashboard->getDatasetInstances()[0]->getAlerts()[0]->getId());
        $this->assertNull($dashboard->getDatasetInstances()[0]->getAlerts()[0]->getAlertGroupId());
        $this->assertNull($dashboard->getDatasetInstances()[0]->getAlerts()[1]->getId());
        $this->assertNull($dashboard->getDatasetInstances()[0]->getAlerts()[1]->getAlertGroupId());


    }


    public function testDashboardWithParentIdReturnedWithParentDataMergedOnGet() {


        AuthenticationHelper::login("admin@kinicart.com", "password");

        $parentDashboardRaw = Dashboard::fetch(1);
        $childDashboardRaw = Dashboard::fetch(2);

        // Get a child dashboard by id.
        $childDashboard = $this->dashboardService->getDashboardById(2);

        // Confirm that it contains the aggregate of the dashboard instances
        $datasetInstances = $childDashboard->getDatasetInstances();
        $this->assertEquals(4, sizeof($datasetInstances));
        $this->assertEquals($childDashboardRaw->getDatasetInstances()[0], $datasetInstances[0]);
        $this->assertEquals($childDashboardRaw->getDatasetInstances()[1], $datasetInstances[1]);
        $this->assertEquals($parentDashboardRaw->getDatasetInstances()[0], $datasetInstances[2]);
        $this->assertEquals($parentDashboardRaw->getDatasetInstances()[1], $datasetInstances[3]);


        // Confirm that layout settings are merged correctly
        $parentRawLayoutSettings = $parentDashboardRaw->getLayoutSettings();
        $childRawLayoutSettings = $childDashboardRaw->getLayoutSettings();
        $childLayoutSettings = $childDashboard->getLayoutSettings();

        // Grid settings - ensure parent ones are added locked
        $this->assertEquals(4, sizeof($childLayoutSettings["grid"]));

        $parentGrid1 = $parentRawLayoutSettings["grid"][0];
        $parentGrid1["locked"] = 1;
        $parentGrid2 = $parentRawLayoutSettings["grid"][1];
        $parentGrid2["locked"] = 1;

        $this->assertEquals([$parentGrid1, $parentGrid2], array_slice($childLayoutSettings["grid"], 0, 2));
        $this->assertEquals($childRawLayoutSettings["grid"], array_slice($childLayoutSettings["grid"], 2));


        // Parameters - check these are merged
        $parameters = $childLayoutSettings["parameters"];
        $this->assertEquals(3, sizeof($parameters));
        $this->assertTrue(isset($parameters["childParam"]));
        $this->assertEquals("childValue", $parameters["childParam"]["value"]);
        $this->assertTrue(isset($parameters["parentParam1"]));
        $this->assertEquals("childParentValue", $parameters["parentParam1"]["value"]);
        $this->assertTrue(isset($parameters["parentParam2"]));
        $this->assertEquals("value2", $parameters["parentParam2"]["value"]);


        // Check other keys merged as expected
        $this->assertEquals(array_merge($childRawLayoutSettings["charts"], $parentRawLayoutSettings["charts"]), $childLayoutSettings["charts"]);
        $this->assertEquals(array_merge($childRawLayoutSettings["metric"], $parentRawLayoutSettings["metric"]), $childLayoutSettings["metric"]);
        $this->assertEquals(array_merge($childRawLayoutSettings["dependencies"], $parentRawLayoutSettings["dependencies"]), $childLayoutSettings["dependencies"]);
        $this->assertEquals(array_merge($childRawLayoutSettings["general"], $parentRawLayoutSettings["general"]), $childLayoutSettings["general"]);


    }


    public function testWhenChildDashboardSavedParentAssetsAreRemoved() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $originalDashboardRaw = Dashboard::fetch(2);

        // Get a child dashboard by id.
        $childDashboard = $this->dashboardService->getDashboardById(2);

        // Save the dashboard
        $this->dashboardService->saveDashboard($childDashboard, "project1", 1);

        // Grab the raw version and check all parent info stripped.
        $childDashboardRaw = Dashboard::fetch(2);

        // Check all parent info removed
        $this->assertEquals($originalDashboardRaw, $childDashboardRaw);


    }

    public function testCanExtendDashboardAndGetANewOneWithParentLink() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $parentDashboard = $this->dashboardService->getDashboardById(2);

        $extended = $this->dashboardService->extendDashboard(2);
        $this->assertEquals(null, $extended->getId());
        $this->assertEquals(2, $extended->getParentDashboardId());
        $this->assertEquals("Test Child Dashboard Extended", $extended->getTitle());
        $this->assertEquals($parentDashboard->getDatasetInstances(), $extended->getDatasetInstances());

        // Lock all parent and confirm extension
        $parentLayout = $parentDashboard->getLayoutSettings();
        $parentLayout["grid"][0]["locked"] = 1;
        $parentLayout["grid"][1]["locked"] = 1;
        $parentLayout["grid"][2]["locked"] = 1;
        $parentLayout["grid"][3]["locked"] = 1;

        $this->assertEquals($parentLayout, $extended->getLayoutSettings());

    }


    public function testReadOnlyFlagSetOnSummaryCorrectlyIfAccountIdNullAndLoggedInAsRegularUser() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSet = new Dashboard(new DashboardSummary("Hello"), 1, null);
        $summary = $dataSet->returnSummary();
        $this->assertFalse($summary->isReadOnly());


        $dataSet = new Dashboard(new DashboardSummary("Hello"), null, null);
        $summary = $dataSet->returnSummary();
        $this->assertFalse($summary->isReadOnly());


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSet = new Dashboard(new DashboardSummary("Hello"), 1, null);
        $summary = $dataSet->returnSummary();
        $this->assertFalse($summary->isReadOnly());


        $dataSet = new Dashboard(new DashboardSummary("Hello"), null, null);
        $summary = $dataSet->returnSummary();
        $this->assertTrue($summary->isReadOnly());

    }


    public function testCanUpdateDashboardMetaData() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $dashboard = new DashboardSummary("Johnny 5");
        $id = $this->dashboardService->saveDashboard($dashboard, null, 2);

        $searchResult = new DashboardSearchResult($id, "Updated Title", "My special summary", "My description", [new CategorySummary("Account 2", "Account 2", "account2")], null);

        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            [new CategorySummary("Account 2", "Account 2", "account2")], 2, null
        ]);


        $this->dashboardService->updateDashboardMetaData($searchResult);


        $dashboard = $this->dashboardService->getDashboardById($id);
        $this->assertEquals("Updated Title", $dashboard->getTitle());
        $this->assertEquals("My special summary", $dashboard->getSummary());
        $this->assertEquals("My description", $dashboard->getDescription());
        $this->assertEquals([new CategorySummary("Account2", "An account wide category available to account 2", "account2")], $dashboard->getCategories());

    }


}