<?php

namespace Kinintel\Test\Services\ImportExport;

use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Alert\AlertGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Services\Alert\AlertService;
use Kinintel\Services\ImportExport\KinintelProjectExporter;
use Kinintel\TestBase;
use Kinintel\ValueObjects\ImportExport\ProjectExportConfig;
use PHPUnit\Framework\MockObject\MockObject;

include_once "autoloader.php";

class KinintelProjectExporterTest extends TestBase {

    /**
     * @var KinintelProjectExporter
     */
    private $exporter;

    /**
     * @var MockObject|AlertService
     */
    private $alertService;

    /**
     * @var MockObject|AlertService
     */
    private $notificationService;


    public function setUp(): void {
        $this->notificationService = MockObjectProvider::mock(NotificationService::class);
        $this->notificationService->returnValue("listNotificationGroups", [
            new NotificationGroupSummary("Example Group 1", [], [], 3),
            new NotificationGroupSummary("Example Group 2", [], [], 5),
            new NotificationGroupSummary("Example Group 3", [], [], 7)
        ], [
            PHP_INT_MAX, 0, "testProject", 5
        ]);

        $this->alertService = MockObjectProvider::mock(AlertService::class);

        $this->exporter = new KinintelProjectExporter($this->notificationService, $this->alertService);
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

        $resources = $this->exporter->getExportableProjectResources(5, "testProject");

        $this->assertEquals([
            new ProjectExportResource(25, "Test Alert 1"),
            new ProjectExportResource(36, "Test Alert 2")
        ], $resources->getResourcesByType()["alertGroups"]);

    }


    public function testExporterExportsAlertGroupsCorrectly() {

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


        $export = $this->exporter->exportProject(5, "testProject",
            new ProjectExportConfig([3], [25 => true]));


        $this->assertEquals([
            new AlertGroupSummary("Test Alert 1", [
                new ScheduledTaskTimePeriod(null, null, 14, 32)
            ], [new NotificationGroupSummary("Example Group 1", null, null, -1)], "Brand alerts", "The following alerts have been generated",
                "Thanks very much", "test", null, null, null, null, -1)
        ], $export->getAlertGroups());

        $this->assertEquals(new ProjectExportConfig([], [-1 => true]), $export->getExportConfig());


    }


}