<?php

namespace Kinintel\Test\Services\ImportExport;

use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\ValueObjects\ImportExport\ProjectImportAnalysis;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Services\Alert\AlertService;
use Kinintel\Services\ImportExport\KinintelProjectImporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\ImportExport\ProjectExport;
use Kinintel\ValueObjects\ImportExport\ProjectExportConfig;

include_once "autoloader.php";

class KinintelProjectImporterTest extends TestBase {

    /**
     * @var KinintelProjectImporter
     */
    private $projectImporter;


    /**
     * @var MockObject|NotificationService
     */
    private $notificationService;


    /**
     * @var MockObject|AlertService
     */
    private $alertService;


    public function setUp(): void {
        $this->notificationService = MockObjectProvider::mock(NotificationService::class);
        $this->alertService = MockObjectProvider::mock(AlertService::class);
        $this->projectImporter = new KinintelProjectImporter($this->notificationService, $this->alertService);

    }

    public function testImportAnalysisChecksAlertGroupsAndReturnsCreateIgnoreOrUpdateAsRequired() {

        $this->notificationService->returnValue("listNotificationGroups", []);

        $this->alertService->returnValue("listAlertGroups", [
            new AlertGroupSummary("Example Alert 1", [], [], null, null, null, null, null, null, null, null, 1),
            new AlertGroupSummary("Example Alert 3", [], [], null, null, null, null, null, null, null, null, 3)
        ], [
            "", PHP_INT_MAX, 0, "testProject", 5
        ]);

        $export = new ProjectExport(new ProjectExportConfig([], [1 => false, 2 => false, 3 => true]), [], [
            new AlertGroupSummary("Example Alert 1", [], [], null, null, null, null, null, null, null, null, 1),
            new AlertGroupSummary("Example Alert 2", [], [], null, null, null, null, null, null, null, null, 2),
            new AlertGroupSummary("Example Alert 3", [], [], null, null, null, null, null, null, null, null, 3)

        ]);

        $this->assertEquals(new ProjectImportAnalysis(date("Y-m-d H:i:s"), [
            "Notification Groups" => [],
            "Alert Groups" => [
                new ProjectImportResource(1, "Example Alert 1", ProjectImportResourceStatus::Ignore),
                new ProjectImportResource(2, "Example Alert 2", ProjectImportResourceStatus::Create),
                new ProjectImportResource(3, "Example Alert 3", ProjectImportResourceStatus::Update)
            ]
        ]), $this->projectImporter->analyseImport(5, "testProject", $export));


    }


    public function testCanCreateOrUpdateAlertGroupOnImportAccordingToStatus() {

        $this->notificationService->returnValue("listNotificationGroups", []);

        $this->alertService->returnValue("listAlertGroups", [
            new AlertGroupSummary("Example Alert 1", [], [], null, null, null, null, null, null, null, null, 25),
            new AlertGroupSummary("Example Alert 3", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [], null, null, null, null, null, null, null, null, 33)
        ], [
            "", PHP_INT_MAX, 0, "testProject", 5
        ]);

        $export = new ProjectExport(new ProjectExportConfig([], [-1 => false, -2 => false, -3 => true]), [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1)], [
            new AlertGroupSummary("Example Alert 1", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, -1),
            new AlertGroupSummary("Example Alert 2", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, -2),
            new AlertGroupSummary("Example Alert 3", [new ScheduledTaskTimePeriod(null, null, 10, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, -1)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, -3)
        ]);

        $this->projectImporter->importProject(5, "testProject", $export);

        $this->notificationService->returnValue("saveNotificationGroup", 67, [
            new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL),
            "testProject", 5
        ]);

        // Check for the new create for alert 2
        $this->assertTrue($this->alertService->methodWasCalled("saveAlert", [
            new AlertGroupSummary("Example Alert 2", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, 67)], "New alert signal", "Alerts start here", "Thanks for the feedback"),
            "testProject", 5
        ]));

        // Check for the update to alert 3
        $this->assertTrue($this->alertService->methodWasCalled("saveAlert", [
            new AlertGroupSummary("Example Alert 3", [new ScheduledTaskTimePeriod(null, null, 5, 30)], [new NotificationGroupSummary("Notification Group 1", [], NotificationGroupSummary::COMMUNICATION_METHOD_EMAIL, 67)], "New alert signal", "Alerts start here", "Thanks for the feedback", null, null, null, null, null, 33),
            "testProject", 5
        ]));


        // Ensure save was only called twice to ensure only our expectations were called.
        $this->assertEquals(2, sizeof($this->alertService->getMethodCallHistory("saveAlertGroup")));


    }


}