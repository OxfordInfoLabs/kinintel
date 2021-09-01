<?php

namespace Kinintel\Test\Services\Alert;

use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinintel\Objects\Alert\AlertGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Objects\Alert\AlertGroupTimePeriod;
use Kinintel\Services\Alert\AlertService;
use Kinintel\TestBase;

include_once "autoloader.php";

class AlertServiceTest extends TestBase {

    /**
     * @var AlertService
     */
    private $alertService;


    /**
     * Setup function
     */
    public function setUp(): void {
        $this->alertService = new AlertService();
    }


    public function testCanCreateListUpdateAndDeleteAlertGroups() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $alertGroup = new AlertGroupSummary("My Alert Group", [
            new ScheduledTaskTimePeriod(4, null, 13, 30),
            new ScheduledTaskTimePeriod(6, null, 13, 30),
        ], [
            new NotificationGroupSummary("Bobby Brown", [
                new NotificationGroupMember(null, "test@oxil.uk")
            ])
        ]);


        $groupId = $this->alertService->saveAlertGroup($alertGroup, null, 1);
        $this->assertNotNull($groupId);

        /**
         * @var AlertGroup $alertGroup
         */
        $alertGroup = AlertGroup::fetch($groupId);

        $this->assertEquals("My Alert Group", $alertGroup->getTitle());

        $scheduledTask = $alertGroup->getScheduledTask();
        $this->assertEquals("alertgroup", $scheduledTask->getTaskIdentifier());
        $this->assertEquals(["alertGroupId" => $groupId], $scheduledTask->getConfiguration());
        $this->assertEquals("Alert Group: My Alert Group (Account 1)", $scheduledTask->getDescription());
        $this->assertNotNull($scheduledTask->getNextStartTime());
        $this->assertEquals(ScheduledTask::STATUS_PENDING, $scheduledTask->getStatus());
        $this->assertEquals([
            new ScheduledTaskTimePeriod(4, null, 13, 30, $scheduledTask->getTimePeriods()[0]->getId()),
            new ScheduledTaskTimePeriod(6, null, 13, 30, $scheduledTask->getTimePeriods()[1]->getId()),
        ], $scheduledTask->getTimePeriods());

    }

}