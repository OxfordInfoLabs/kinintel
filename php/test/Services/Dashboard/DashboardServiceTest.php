<?php

namespace Kinintel\Services\Dashboard;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Dataset;
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


    public function setUp(): void {
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->dashboardService = new DashboardService($this->datasetService);
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

    public function testCanCreateAndRetrieveValidDashboard() {

        $dashboard = new Dashboard("Johnny 5");
        $this->dashboardService->saveDashboard($dashboard);

        $reDashboard = $this->dashboardService->getDashboardById($dashboard->getId());
        $this->assertEquals("Johnny 5", $reDashboard->getTitle());


        $dashboard = new Dashboard("Test Instance", [
            new DashboardDatasetInstance("brandnew", null, "test-json", [
                new TransformationInstance("filterquery", new FilterQuery([
                    new Filter("value", "bingo")
                ]))
            ])
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);

        $this->dashboardService->saveDashboard($dashboard);

        $reDashboard = $this->dashboardService->getDashboardById($dashboard->getId());
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
            "logic" => "and",
            "filterJunctions" => []
        ])], $dashboardDatasetInstance->getTransformationInstances());

    }


    public function testCanGetEvaluatedDatasetForValidDashboardDatasetInstanceUsingInstanceId() {

        $dataSetInstance = new DatasetInstance("Test instance", "test-json");
        $dataSetInstance->save();

        // Save a dashboard
        $dashboard = new Dashboard("Test Instance", [
            new DashboardDatasetInstance("brandnew", $dataSetInstance->getId())
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);
        $this->dashboardService->saveDashboard($dashboard);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformation = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById", $dataSet,
            [
                $dataSetInstance->getId(), [$transformation]
            ]);

        $evaluatedDataset = $this->dashboardService->getEvaluatedDataSetForDashboardDataSetInstance($dashboard->getId(), "brandnew", [
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
        $dashboard = new Dashboard("Test Instance", [
            $dashboardDataSetInstance
        ], [
            "color" => "green",
            "font" => "Arial"
        ]);
        $this->dashboardService->saveDashboard($dashboard);

        $dataSet = MockObjectProvider::instance()->getMockInstance(Dataset::class);
        $transformation = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstance", $dataSet,
            [
                $dashboardDataSetInstance, [$transformation]
            ]);

        $evaluatedDataset = $this->dashboardService->getEvaluatedDataSetForDashboardDataSetInstance($dashboard->getId(),
            "otherset", [$transformation]);
        $this->assertEquals($dataSet, $evaluatedDataset);

    }
}