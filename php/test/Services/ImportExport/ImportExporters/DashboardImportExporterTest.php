<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\Services\ImportExport\ImportExporters\DashboardImportExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\ImportExport\ExportConfig\DashboardExportConfig;
use Kinintel\ValueObjects\Transformation\Combine\CombineTransformation;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DashboardImportExporterTest extends TestBase {

    /**
     * @var DashboardImportExporter
     */
    private $importExporter;

    /**
     * @var DashboardService
     */
    private $dashboardService;


    public function setUp(): void {
        $this->dashboardService = MockObjectProvider::mock(DashboardService::class);
        $this->importExporter = new DashboardImportExporter($this->dashboardService);

        ImportExporter::resetData();
    }

    public function testCanGetExportableResourcesForProjectDashboards() {

        $summary1 = new DashboardSummary("Dashboard Number 1");
        $summary1->setId(25);

        $summary2 = new DashboardSummary("Dashboard Number 2");
        $summary2->setId(50);

        $this->dashboardService->returnValue("getAllDashboards", [
            $summary1, $summary2
        ], [
            "testProject", 5
        ]);

        $resources = $this->importExporter->getExportableProjectResources(5, "testProject");

        $this->assertEquals([
            new ProjectExportResource(25, "Dashboard Number 1", new DashboardExportConfig(true, true, true)),
            new ProjectExportResource(50, "Dashboard Number 2", new DashboardExportConfig(true, true, true))
        ], $resources);

    }

    public function testCanCreateExportObjectsForIncludedDashboardsWithoutAlerts() {

        $summary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 66))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary1->setId(25);

        $summary2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, "testkey", [
                new TransformationInstance("combine", new CombineTransformation("testkey2", null))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary2->setId(50);

        $this->dashboardService->returnValue("getAllDashboards", [
            $summary1, $summary2
        ], [
            "testProject", 5
        ]);

        ImportExporter::getNewExportPK("datasets", 33);
        ImportExporter::getNewExportPK("datasets", 66);
        ImportExporter::getNewExportPK("datasources", "testkey");
        ImportExporter::getNewExportPK("datasources", "testkey2");
        ImportExporter::getNewExportPK("alertGroups", 5);

        $objects = $this->importExporter->createExportObjects(5, "testProject", [
            25 => new DashboardExportConfig(true, false, false),
            50 => new DashboardExportConfig(true, false, false)
        ], []);

        $expectedSummary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", -1, null, [
                new TransformationInstance("join", new JoinTransformation(null, -2))
            ], [], -1)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedSummary1->setId(-1);

        $expectedSummary2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [], -2)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedSummary2->setId(-2);


        $this->assertEquals([$expectedSummary1, $expectedSummary2], $objects);

    }

    public function testCanCreateExportObjectsForIncludedDashboardsWithAlerts() {

        $summary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 66))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary1->setId(25);

        $summary2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, "testkey", [
                new TransformationInstance("combine", new CombineTransformation("testkey2", null))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary2->setId(50);

        $this->dashboardService->returnValue("getAllDashboards", [
            $summary1, $summary2
        ], [
            "testProject", 5
        ]);

        ImportExporter::getNewExportPK("datasets", 33);
        ImportExporter::getNewExportPK("datasets", 66);
        ImportExporter::getNewExportPK("datasources", "testkey");
        ImportExporter::getNewExportPK("datasources", "testkey2");
        ImportExporter::getNewExportPK("alertGroups", 5);

        $objects = $this->importExporter->createExportObjects(5, "testProject", [
            25 => new DashboardExportConfig(true, true, false),
            50 => new DashboardExportConfig(true, true, false)
        ], []);

        $expectedSummary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", -1, null, [
                new TransformationInstance("join", new JoinTransformation(null, -2))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", -1)
            ], -1)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedSummary1->setId(-1);

        $expectedSummary2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", -1)
            ], -2)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedSummary2->setId(-2);


        $this->assertEquals([$expectedSummary1, $expectedSummary2], $objects);

    }

    public function testCanCreateExportObjectsForDashboardsWithParentIds() {


        $summary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 66))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary1->setId(25);
        $summary1->setParentDashboardId(50);

        $summary2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, "testkey", [
                new TransformationInstance("combine", new CombineTransformation("testkey2", null))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary2->setId(50);

        $this->dashboardService->returnValue("getAllDashboards", [
            $summary1, $summary2
        ], [
            "testProject", 5
        ]);

        ImportExporter::getNewExportPK("datasets", 33);
        ImportExporter::getNewExportPK("datasets", 66);
        ImportExporter::getNewExportPK("datasources", "testkey");
        ImportExporter::getNewExportPK("datasources", "testkey2");
        ImportExporter::getNewExportPK("alertGroups", 5);

        $objects = $this->importExporter->createExportObjects(5, "testProject", [
            25 => new DashboardExportConfig(true, true, false),
            50 => new DashboardExportConfig(true, true, false)
        ], []);

        $expectedSummary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", -1, null, [
                new TransformationInstance("join", new JoinTransformation(null, -2))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", -1)
            ], -1)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedSummary1->setId(-1);
        $expectedSummary1->setParentDashboardId(-2);

        $expectedSummary2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", -1)
            ], -2)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedSummary2->setId(-2);


        $this->assertEquals([$expectedSummary1, $expectedSummary2], $objects);


    }


    public function testCanGenerateImportAnalysisForExportedDashboards() {


        $summary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 66))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary1->setId(25);


        $this->dashboardService->returnValue("getAllDashboards", [
            $summary1
        ], [
            "testProject", 5
        ]);


        $dashboard1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", -1, null, [
                new TransformationInstance("join", new JoinTransformation(null, -2))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", -1)
            ], -1)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard1->setId(-1);

        $dashboard2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", -1)
            ], -3)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard2->setId(-3);

        $exported = [$dashboard1, $dashboard2];


        $importAnalysis = $this->importExporter->analyseImportObjects(5, "testProject", $exported, [
            -1 => new DashboardExportConfig(true, false),
            -2 => new DashboardExportConfig(false),
            -3 => new DashboardExportConfig(true, true, true)
        ]);


        $this->assertEquals([
            new ProjectImportResource(-1, "Dashboard Number 1", ProjectImportResourceStatus::Update, 25),
            new ProjectImportResource(-3, "Dashboard Number 2", ProjectImportResourceStatus::Create)
        ], $importAnalysis);

    }

    public function testCanImportNewAndExistingDashboardsWhereAlertsAreNotIncluded() {


        $summary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 66))
            ], [new Alert("Test", "totalCount", [], [], "Test", "Testcta", "Test Summary")])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary1->setId(25);


        $this->dashboardService->returnValue("getAllDashboards", [
            $summary1
        ], [
            "testProject", 5
        ]);


        $dashboard1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", -1, null, [
                new TransformationInstance("join", new JoinTransformation(null, -2))
            ], [], -1)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard1->setId(-1);

        $dashboard2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [], -3)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard2->setId(-3);

        $exported = [$dashboard1, $dashboard2];

        ImportExporter::setImportItemIdMapping("datasets", -1, 33);
        ImportExporter::setImportItemIdMapping("datasets", -2, 44);
        ImportExporter::setImportItemIdMapping("datasources", -1, "testds1");
        ImportExporter::setImportItemIdMapping("datasources", -2, "testds2");

        $this->importExporter->importObjects(5, "testProject", $exported, [
            -1 => new DashboardExportConfig(true, false),
            -2 => new DashboardExportConfig(false),
            -3 => new DashboardExportConfig(true, false)
        ]);

        $expectedDashboard1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 44))
            ], [])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedDashboard1->setId(25);

        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [$expectedDashboard1, "testProject", 5]));


        $expectedDashboard2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, "testds1", [
                new TransformationInstance("combine", new CombineTransformation("testds2", null))
            ], [])
        ], ["prop" => "Hello"], ["layout" => 44]);

        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [$expectedDashboard2, "testProject", 5]));


    }


    public function testCanImportNewAndExistingDashboardsWhereAlertsAreIncludedWithOrWithoutTemplateUpdate() {


        $summary1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 66))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary1->setId(44);

        $summary2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, "testkey", [
                new TransformationInstance("combine", new CombineTransformation("testkey2", null))
            ], [
                new Alert("James Smith", "rowcount", [], [], "Test Template", "testcta", "Test Summary", 5)
            ])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $summary2->setId(55);

        $this->dashboardService->returnValue("getAllDashboards", [
            $summary1, $summary2
        ], [
            "testProject", 5
        ]);


        $dashboard1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", -1, null, [
                new TransformationInstance("join", new JoinTransformation(null, -2))
            ], [new Alert("James Smith", "rowcount", [], [], "Updated Template", "updatedcta", "Updated Summary", -1)], -1)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard1->setId(-1);

        $dashboard2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [new Alert("James Smith", "total", ["test" => 1], [new FilterTransformation([new Filter(3, 4)])], "Updated Template", "updatedcta", "Updated Summary", -1)], -2)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard2->setId(-2);

        $dashboard3 = new DashboardSummary("Dashboard Number 3", [
            new DashboardDatasetInstance("instance3", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [new Alert("James Smith", "rowcount", [], [], "Updated Template", "updatedcta", "Updated Summary", -1)], -3)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard3->setId(-3);

        $exported = [$dashboard1, $dashboard2, $dashboard3];


        ImportExporter::setImportItemIdMapping("datasets", -1, 33);
        ImportExporter::setImportItemIdMapping("datasets", -2, 44);
        ImportExporter::setImportItemIdMapping("datasources", -1, "testds1");
        ImportExporter::setImportItemIdMapping("datasources", -2, "testds2");
        ImportExporter::setImportItemIdMapping("alertGroups", -1, 23);


        $this->importExporter->importObjects(5, "testProject", $exported, [
            -1 => new DashboardExportConfig(true, true, true),
            -2 => new DashboardExportConfig(true, true, false),
            -3 => new DashboardExportConfig(true, true, true)
        ]);


        $expectedDashboard1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 44))
            ], [new Alert("James Smith", "rowcount", [], [], "Updated Template", "updatedcta", "Updated Summary", 23)])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedDashboard1->setId(44);

        $expectedDashboard2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, "testds1", [
                new TransformationInstance("combine", new CombineTransformation("testds2", null))
            ], [new Alert("James Smith", "total", ["test" => 1], [new FilterTransformation([new Filter(3, 4)])], "Test Template", "testcta", "Test Summary", 23)])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedDashboard2->setId(55);

        $expectedDashboard3 = new DashboardSummary("Dashboard Number 3", [
            new DashboardDatasetInstance("instance3", null, "testds1", [
                new TransformationInstance("combine", new CombineTransformation("testds2", null))
            ], [new Alert("James Smith", "rowcount", [], [], "Updated Template", "updatedcta", "Updated Summary", 23)])
        ], ["prop" => "Hello"], ["layout" => 44]);

        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [
            $expectedDashboard1, "testProject", 5
        ]));

        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [
            $expectedDashboard2, "testProject", 5
        ]));


        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [
            $expectedDashboard3, "testProject", 5
        ]));
    }

    public function testParentDashboardIdsAreCorrectlyRelatedOnImport() {

        $this->dashboardService->returnValue("getAllDashboards", [
        ], [
            "testProject", 5
        ]);


        $dashboard1 = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", -1, null, [
                new TransformationInstance("join", new JoinTransformation(null, -2))
            ], [], -1)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard1->setId(-1);
        $dashboard1->setParentDashboardId(-2);

        $dashboard2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, -1, [
                new TransformationInstance("combine", new CombineTransformation(-2, null))
            ], [], -2)
        ], ["prop" => "Hello"], ["layout" => 44]);
        $dashboard2->setId(-2);

        $exported = [$dashboard1, $dashboard2];


        ImportExporter::setImportItemIdMapping("datasets", -1, 33);
        ImportExporter::setImportItemIdMapping("datasets", -2, 44);
        ImportExporter::setImportItemIdMapping("datasources", -1, "testds1");
        ImportExporter::setImportItemIdMapping("datasources", -2, "testds2");
        ImportExporter::setImportItemIdMapping("alertGroups", -1, 23);


        $expectedDashboard1FirstSave = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 44))
            ], [])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedDashboard1FirstSave->setParentDashboardId(-2);

        $expectedDashboard1AfterFirstSave = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 44))
            ], [])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedDashboard1AfterFirstSave->setId(44);
        $expectedDashboard1AfterFirstSave->setParentDashboardId(-2);

        $expectedDashboard1SecondSave = new DashboardSummary("Dashboard Number 1", [
            new DashboardDatasetInstance("instance1", 33, null, [
                new TransformationInstance("join", new JoinTransformation(null, 44))
            ], [])
        ], ["prop" => "Hello"], ["layout" => 44]);
        $expectedDashboard1SecondSave->setId(44);
        $expectedDashboard1SecondSave->setParentDashboardId(55);

        $expectedDashboard2 = new DashboardSummary("Dashboard Number 2", [
            new DashboardDatasetInstance("instance2", null, "testds1", [
                new TransformationInstance("combine", new CombineTransformation("testds2", null))
            ], [])
        ], ["prop" => "Hello"], ["layout" => 44]);


        $this->dashboardService->returnValue("saveDashboard", 44, [
            $expectedDashboard1FirstSave, "testProject", 5
        ]);

        $this->dashboardService->returnValue("saveDashboard", 55, [
            $expectedDashboard2, "testProject", 5
        ]);


        $this->dashboardService->returnValue("getShallowDashboard", $expectedDashboard1AfterFirstSave, [44]);


        $this->importExporter->importObjects(5, "testProject", $exported, [
            -1 => new DashboardExportConfig(true, false),
            -2 => new DashboardExportConfig(true, false)
        ]);


        // Check all expected saves happen
        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [
            $expectedDashboard1FirstSave, "testProject", 5
        ]));

        
        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [
            $expectedDashboard1SecondSave, "testProject", 5
        ]));

        $this->assertTrue($this->dashboardService->methodWasCalled("saveDashboard", [
            $expectedDashboard2, "testProject", 5
        ]));


    }


}