<?php


namespace Kinintel\Test\Services\ImportExport;


use Kiniauth\Objects\Account\AccountSummary;
use Kiniauth\Objects\Account\ProjectSummary;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Account\ProjectService;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Objects\Dashboard\Dashboard;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\Services\Alert\AlertService;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\ImportExport\ExportService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\ImportExport\Export;
use Kinintel\ValueObjects\ImportExport\ExportableResources;
use Kinintel\ValueObjects\ImportExport\ResourceExportDescriptor;

include_once "autoloader.php";

class ExportServiceTest extends TestBase {

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
     * @var MockObject
     */
    private $alertService;

    /**
     * @var MockObject
     */
    private $projectService;

    /**
     * @var MockObject
     */
    private $accountService;

    /**
     * @var ExportService
     */
    private $exportService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->dashboardService = MockObjectProvider::instance()->getMockInstance(DashboardService::class);
        $this->alertService = MockObjectProvider::instance()->getMockInstance(AlertService::class);
        $this->projectService = MockObjectProvider::instance()->getMockInstance(ProjectService::class);
        $this->accountService = MockObjectProvider::instance()->getMockInstance(AccountService::class);

        $this->exportService = new ExportService($this->datasourceService, $this->datasetService, $this->dashboardService, $this->alertService,
            $this->projectService, $this->accountService);


    }


    public function testAllExportableResourcesReturnedCorrectlyForProjectScopeSupplied() {

        $datasourceInstanceSearchResults = [
            new DatasourceInstanceSearchResult("test-ds-1", "Test DS 1", "json"),
            new DatasourceInstanceSearchResult("test-ds-2", "Test DS 2", "json")
        ];

        $datasetInstanceSearchResults = [
            new DatasetInstanceSearchResult(25, "Test DI 1", "Hello world", "", [], 76),
            new DatasetInstanceSearchResult(76, "Test DI 2", "Hello world", "", [], null, "test-ds-2"),
            new DatasetInstanceSearchResult(99, "Test DI 3", "Hello world", "", [], 11)
        ];

        $dashboardSummaries = [
            new DashboardSummary("Test DB 1", [], [], [], true, false, [], false, "", "", [], 11, false, 54),
            new DashboardSummary("Test DB 2", [], [], [], true, false, [], false, "", "", [], 54, false, 9)
        ];


        $this->datasourceService->returnValue("filterDatasourceInstances", $datasourceInstanceSearchResults, [
            "", PHP_INT_MAX, 0, false, "myKey", 1
        ]);

        $this->datasetService->returnValue("filterDataSetInstances", $datasetInstanceSearchResults, [
            "", [], [], "myKey", 0, PHP_INT_MAX, 1
        ]);

        $this->dashboardService->returnValue("getAllDashboards", $dashboardSummaries, [
            "myKey", 1
        ]);

        $expectedResult = new ExportableResources($datasourceInstanceSearchResults, $datasetInstanceSearchResults, $dashboardSummaries);

        $result = $this->exportService->getExportableResources("myKey", 1);
        $this->assertEquals($expectedResult, $result);


    }


    public function testExportCreatedCorrectlyFoResourceExportDescriptorForProjectsAndAccounts() {


        $datasource = new DatasourceInstance("instance-1", "My Instance", "custom", ["foo" => "bar", "test" => "check"], "dap_data");
        $datasource->setAccountId(1);
        $datasource->setProjectKey("mytestproject");
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $datasource, ["instance-1"]);

        $dataset = new DatasetInstance(new DatasetInstanceSummary("Test DS", "test-ds"), 1, "mytestproject");
        $this->datasetService->returnValue("getFullDataSetInstance", $dataset, [11]);

        $dashboard = new Dashboard(new DashboardSummary("Test DB"), 1, "mytestproject");
        $this->dashboardService->returnValue("getFullDashboard", $dashboard, [21]);

        $projectAlertGroups = [
            new AlertGroupSummary("My Alert Group"),
            new AlertGroupSummary("Your Alert Group")
        ];
        $this->alertService->returnValue("listAlertGroups", $projectAlertGroups, [
            "", PHP_INT_MAX, 0, "mytestproject", 1
        ]);

        $this->projectService->returnValue("getProject", new ProjectSummary("Amazing Project", "Amazing Project", "mytestproject"), [
            "mytestproject",
            1
        ]);


        $accountAlertGroups = [
            new AlertGroupSummary("My Account Alert Group"),
            new AlertGroupSummary("Your Account Alert Group")
        ];
        $this->alertService->returnValue("listAlertGroups", $accountAlertGroups, [
            "", PHP_INT_MAX, 0, null, 1
        ]);

        $this->accountService->returnValue("getAccountSummary", new AccountSummary(1, "Account for Export"), [
            1
        ]);


        // Do a default one for a project
        $resourceExportDescriptor = new ResourceExportDescriptor("mytestproject", ["instance-1"], [11], [21]);
        $result = $this->exportService->exportResources($resourceExportDescriptor, 1);
        $this->assertEquals(new Export(Export::SCOPE_PROJECT, "Amazing Project", [$datasource], [$dataset], [$dashboard], $projectAlertGroups), $result);

        // Do one with a custom title and version for a project
        $resourceExportDescriptor = new ResourceExportDescriptor("mytestproject", ["instance-1"], [11], [21], "Custom Title", 25);
        $result = $this->exportService->exportResources($resourceExportDescriptor, 1);
        $this->assertEquals(new Export(Export::SCOPE_PROJECT, "Custom Title", [$datasource], [$dataset], [$dashboard], $projectAlertGroups, 25), $result);

        // Do a default one for an account
        $resourceExportDescriptor = new ResourceExportDescriptor(1, ["instance-1"], [11], [21], null, null, Export::SCOPE_ACCOUNT);
        $result = $this->exportService->exportResources($resourceExportDescriptor, 1);
        $this->assertEquals(new Export(Export::SCOPE_ACCOUNT, "Account for Export", [$datasource], [$dataset], [$dashboard], $accountAlertGroups), $result);

        // Do one with a custom title and version for an account
        $resourceExportDescriptor = new ResourceExportDescriptor(1, ["instance-1"], [11], [21], "Custom Account Title", 33, Export::SCOPE_ACCOUNT);
        $result = $this->exportService->exportResources($resourceExportDescriptor, 1);
        $this->assertEquals(new Export(Export::SCOPE_ACCOUNT, "Custom Account Title", [$datasource], [$dataset], [$dashboard], $accountAlertGroups, 33), $result);


    }


}