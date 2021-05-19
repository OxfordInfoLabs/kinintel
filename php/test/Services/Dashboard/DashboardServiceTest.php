<?php

namespace Kinintel\Services\Dashboard;

use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\TabularDataset;
use Kinintel\ValueObjects\Transformation\Query\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Query\FilterQuery;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

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
        $id = $this->dashboardService->saveDashboard($dashboard, 2, 1);

        $reDashboard = $this->dashboardService->getDashboardById($id);
        $this->assertEquals("Johnny 5", $reDashboard->getTitle());


        $dashboard = new DashboardSummary("Test Instance", [
            new DashboardDatasetInstance("brandnew", null, "test-json", [
                new TransformationInstance("filterquery", new FilterQuery([
                    new Filter("value", "bingo")
                ]))
            ])
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);

        $id = $this->dashboardService->saveDashboard($dashboard, 2, 1);

        $reDashboard = $this->dashboardService->getDashboardById($id);
        $this->assertEquals("Test Instance", $reDashboard->getTitle());
        $dashboardDatasetInstance = $reDashboard->getDatasetInstances()[0];
        $this->assertEquals("brandnew", $dashboardDatasetInstance->getInstanceKey());
        $this->assertEquals("test-json", $dashboardDatasetInstance->getDatasourceInstanceKey());
        $this->assertEquals([new TransformationInstance("filterquery", [
            "filters" => [[
                "fieldName" => "value",
                "value" => "bingo",
                "filterType" => "eq"
            ]],
            "logic" => "AND",
            "filterJunctions" => [],
            "sQLTransformationProcessorKey" => "filterquery"
        ])], $dashboardDatasetInstance->getTransformationInstances());

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
        $id = $this->dashboardService->saveDashboard($dashboard, 2, 1);

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
            new TransformationInstance("filterquery", new FilterQuery([
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
        $id = $this->dashboardService->saveDashboard($dashboard, 2, 1);

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

        $id = $this->dashboardService->saveDashboard($dashboardInstance, 2, "soapSuds");

        $dashboard = Dashboard::fetch($id);
        $this->assertEquals(2, $dashboard->getAccountId());
        $this->assertEquals("soapSuds", $dashboard->getProjectKey());

        $tags = $dashboard->getTags();
        $this->assertEquals(2, sizeof($tags));

        $this->assertEquals("account2", $tags[0]->getTag()->getKey());
        $this->assertEquals("project", $tags[1]->getTag()->getKey());


    }

}