<?php


namespace Kinintel\Test\Services\ImportExport;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\ImportExport\ImportService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\ImportExport\Export;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class ImportServiceTest extends TestBase {


    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var MockObject
     */
    private $datasetService;

    /**
     * @var MockObject
     */
    private $dashboardService;

    /**
     * @var ImportService
     */
    private $importService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->dashboardService = MockObjectProvider::instance()->getMockInstance(DashboardService::class);

        $this->importService = new ImportService($this->datasourceService, $this->datasetService, $this->dashboardService);
    }


    public function testCanAnalyseImportForEmptyTargetProject() {

        $datasource = new DatasourceInstance("myexampledatasource", "My Example Datasource", "custom", [
            "tableName" => "myexampledatasource",
            "otherProp" => "my value"
        ], "maindb");

        // Dataset for attachment
        $dataset = new DatasetInstance(new DatasetInstanceSummary("Example 1", null, null, [
            new TransformationInstance("filter", new FilterTransformation([new Filter("test", "bingo")])),
            new TransformationInstance("join", new JoinTransformation(null, 2))
        ], [], [], null, null, [], null, 1));


        $dashboard1 = new Dashboard(new DashboardSummary("Dashboard 1", [
            new DashboardDatasetInstance("instance1", 1),
            new DashboardDatasetInstance("instance2", null, "myexampledatasource"),
            new DashboardDatasetInstance("instance3", 12, null, [new TransformationInstance("filter", new FilterTransformation([new Filter("test", "bingo")])),
                new TransformationInstance("join", new JoinTransformation(null, 1))]),
            new DashboardDatasetInstance("instance4", null, "externalds", [
                new TransformationInstance("join", new JoinTransformation("myexampledatasource", null))
            ])
        ], null, null, null, null, null, [], 1, false, 2), 1, "myprojectkey");
        $dashboard2 = new Dashboard(new DashboardSummary("Dashboard 2", [], null, null, null, null, null, [], 2, false, 23), 1, "myprojectkey");
        $dashboard3 = new Dashboard(new DashboardSummary("Dashboard 3", [], null, null, null, null, null, [], 3, false, 1), 1, "myprojectkey");


        // Generate an export using resources
        $export = new Export(Export::SCOPE_PROJECT, "Dashboard Export", [$datasource], [$dataset], [$dashboard1, $dashboard2, $dashboard3], []);


        // Programme expected responses
       // $this->datasourceService->returnValue("")

        // Import to project
        $this->importService->importToProject($export, "newproject", 1);


    }


    public function testCustomDatasourcesGetImportedCorrectlyIntoProject() {

        $sourceDatasource = new DatasourceInstance("myexampledatasource", "My Example Datasource", "custom", [
            "tableName" => "myexampledatasource",
            "otherProp" => "my value"
        ], "maindb");

        $export = new Export(Export::SCOPE_PROJECT, "My Export", [$sourceDatasource], [], [], []);

    }


}