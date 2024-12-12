<?php

namespace Kinintel\Test\Services\ImportExport\ImportExporters;

use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Services\Alert\AlertService;
use Kinintel\Services\ImportExport\ImportExporters\AlertGroupImportExporter;
use Kinintel\Services\ImportExport\KinintelProjectExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\ImportExport\ExportConfig\ObjectUpdateSelectionExportConfig;
use Kinintel\ValueObjects\ImportExport\ProjectExportConfig;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class AlertGroupImportExporterTest extends TestBase {


    /**
     * @var AlertGroupImportExporter
     */
    private $importExporter;

    /**
     * @var MockObject|AlertService
     */
    private $alertService;


    public function setUp(): void {

        $this->alertService = MockObjectProvider::mock(AlertService::class);
        $this->importExporter = new AlertGroupImportExporter($this->alertService);
    }


    public function testExporterReturnsAlertGroupsCorrectlyAsExportableProjectResources() {

        $this->alertService->returnValue("listAlertGroups", [
            new AlertGroupSummary("Test Alert 1", [
                new ScheduledTaskTimePeriod(null, null, 14, 32)
            ], [new NotificationGroupSummary("Example Group 1")], "Brand alerts", "The following alerts have been generated",
                "Thanks very much", "test", null, null, null, null, 25),
            new AlertGroupSummary("Test Alert 2", [
                new ScheduledTaskTimePeriod(null, null, 14, 32)
            ], [new NotificationGroupSummary("Example Group 2")], "Brand alerts", "The following alerts have been generated",
                "Thanks very much", "test", null, null, null, null, 36)
        ], [
            "", PHP_INT_MAX, 0, "testProject", 5
        ]);

        $resources = $this->importExporter->getExportableProjectResources(5, "testProject");

        $this->assertEquals([
            new ProjectExportResource(25, "Test Alert 1", new ObjectUpdateSelectionExportConfig(true, true)),
            new ProjectExportResource(36, "Test Alert 2", new ObjectUpdateSelectionExportConfig(true, true))
        ], $resources);

    }


    public function testCreateExportObjectsExportsAlertGroupsCorrectly() {

        $this->alertService->returnValue("listAlertGroups", [
            new AlertGroupSummary("Test Alert 1", [
                new ScheduledTaskTimePeriod(null, null, 14, 32)
            ], [new NotificationGroupSummary("Example Group 1", null, null, 3)], "Brand alerts", "The following alerts have been generated",
                "Thanks very much", "test", null, null, null, null, 25),
            new AlertGroupSummary("Test Alert 2", [
                new ScheduledTaskTimePeriod(null, null, 14, 32)
            ], [new NotificationGroupSummary("Example Group 2", null, null, 5)], "Brand alerts", "The following alerts have been generated",
                "Thanks very much", "test", null, null, null, null, 36)
        ], [
            "", PHP_INT_MAX, 0, "testProject", 5
        ]);


        // Remap Notification groups as if the notification group has been mapped already
        ImportExporter::getNewExportPK("notificationGroups", 3);
        ImportExporter::getNewExportPK("notificationGroups", 5);


        $exportObjects = $this->importExporter->createExportObjects(5, "testProject",
            [25 => new ObjectUpdateSelectionExportConfig(true, true)], []);


        $this->assertEquals([
            new AlertGroupSummary("Test Alert 1", [
                new ScheduledTaskTimePeriod(null, null, 14, 32)
            ], [new NotificationGroupSummary("Example Group 1", null, null, -1)], "Brand alerts", "The following alerts have been generated",
                "Thanks very much", "test", null, null, null, null, -1)
        ], $exportObjects);


    }


    public function testImportAnalysisChecksAlertGroupsAndReturnsCreateIgnoreOrUpdateAsRequired() {


        $this->alertService->returnValue("listAlertGroups", [
            new AlertGroupSummary("Example Alert 1", [], [], null, null, null, null, null, null, null, null, 1),
            new AlertGroupSummary("Example Alert 3", [], [], null, null, null, null, null, null, null, null, 3)
        ], [
            "", PHP_INT_MAX, 0, "testProject", 5
        ]);

        $exportResources = [
            new AlertGroupSummary("Example Alert 1", [], [], null, null, null, null, null, null, null, null, -1),
            new AlertGroupSummary("Example Alert 2", [], [], null, null, null, null, null, null, null, null, -2),
            new AlertGroupSummary("Example Alert 3", [], [], null, null, null, null, null, null, null, null, -3)

        ];

        $this->assertEquals([
                new ProjectImportResource(-1, "Example Alert 1", ProjectImportResourceStatus::Ignore, 1),
                new ProjectImportResource(-2, "Example Alert 2", ProjectImportResourceStatus::Create),
                new ProjectImportResource(-3, "Example Alert 3", ProjectImportResourceStatus::Update, 3)
            ]
            , $this->importExporter->analyseImportObjects(5, "testProject", $exportResources,
                [-1 => new ObjectUpdateSelectionExportConfig(true, false), -2 => new ObjectUpdateSelectionExportConfig(true, false), -3 => new ObjectUpdateSelectionExportConfig(true, true)], null));


    }


    public function testCanCreateOrUpdateAlertGroupOnImportAccordingToStatus() {


        $this->alertService->returnValue("listAlertGroups", [
            new AlertGroupSummary("Example Alert 1", [], [], null, null, null, null, null, null, null, null, 25),
            new AlertGroupSummary("Example Alert 3", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [], null, null, null, null, null, null, null, null, 33)
        ], [
            "", PHP_INT_MAX, 0, "testProject", 5
        ]);

        // Programme single lookup for alert
        $this->alertService->returnValue("getAlertGroup", new AlertGroupSummary("Example Alert 3", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [], null, null, null, null, null, null, null, null, 33), [
            33
        ]);

        $exportResources = [
            new AlertGroupSummary("Example Alert 1", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, -1),
            new AlertGroupSummary("Example Alert 2", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, -2),
            new AlertGroupSummary("Example Alert 3", [new ScheduledTaskTimePeriod(null, null, 10, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, -3)
        ];


        ImportExporter::setImportItemIdMapping("notificationGroups", -1, 67,);

        $this->importExporter->importObjects(5, "testProject", $exportResources,
            [-1 => new ObjectUpdateSelectionExportConfig(true, false), -2 => new ObjectUpdateSelectionExportConfig(true, false), -3 => new ObjectUpdateSelectionExportConfig(true, true)]);


        // Check for the new create for alert 2
        $this->assertTrue($this->alertService->methodWasCalled("saveAlertGroup", [
            new AlertGroupSummary("Example Alert 2", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, 67)], "New alert signal", "Alerts start here", "Thanks for the feedback"),
            "testProject", 5
        ]));

        // Check for the update to alert 3
        $this->assertTrue($this->alertService->methodWasCalled("saveAlertGroup", [
            new AlertGroupSummary("Example Alert 3", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, 67)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, 33),
            "testProject", 5
        ]));


        // Ensure save was only called twice to ensure only our expectations were called.
        $this->assertEquals(2, sizeof($this->alertService->getMethodCallHistory("saveAlertGroup")));


    }


}