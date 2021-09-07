<?php

namespace Kinintel\Test\Services\Alert;

use GuzzleHttp\Handler\Proxy;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
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


    public function testCanCreateGetUpdateAndDeleteAlertGroups() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $notificationGroup = new NotificationGroup(new NotificationGroupSummary("Bobby Brown", [
            new NotificationGroupMember(null, "test@oxil.uk")
        ]), null, 1);
        $notificationGroup->save();

        $alertGroup = new AlertGroupSummary("My Alert Group", [
            new ScheduledTaskTimePeriod(4, null, 13, 30),
            new ScheduledTaskTimePeriod(6, null, 13, 30),
        ], [
            $notificationGroup->returnSummary()
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

        $this->assertEquals([
            new NotificationGroup(new NotificationGroupSummary("Bobby Brown", [
                new NotificationGroupMember(null, "test@oxil.uk", $notificationGroup->getMembers()[0]->getId())
            ], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, $notificationGroup->getId()), null, 1)
        ], $alertGroup->getNotificationGroups());


        // Get the alert group
        $reAlertGroup = $this->alertService->getAlertGroup($groupId);
        $this->assertEquals($alertGroup->returnSummary(), $reAlertGroup);

        // Update an alert group
        $reAlertGroup->setTaskTimePeriods([
            new ScheduledTaskTimePeriod(1, null, 9, 30),
            new ScheduledTaskTimePeriod(28, null, 9, 30),
        ]);


        $reAlertGroup->setTitle("Updated Title");

        // Create new group
        $newGroup = new NotificationGroup(new NotificationGroupSummary("All Friends", [
            new NotificationGroupMember(null, "another@oxil.uk")
        ]), null, 1);
        $newGroup->save();

        $reAlertGroup->setNotificationGroups([
            $newGroup
        ]);

        $this->alertService->saveAlertGroup($reAlertGroup, null, 1);

        /**
         * @var AlertGroup $alertGroup
         */
        $alertGroup = AlertGroup::fetch($groupId);

        $this->assertEquals("Updated Title", $alertGroup->getTitle());

        $scheduledTask = $alertGroup->getScheduledTask();
        $this->assertEquals("alertgroup", $scheduledTask->getTaskIdentifier());
        $this->assertEquals(["alertGroupId" => $groupId], $scheduledTask->getConfiguration());
        $this->assertEquals("Alert Group: My Alert Group (Account 1)", $scheduledTask->getDescription());
        $this->assertNotNull($scheduledTask->getNextStartTime());
        $this->assertEquals(ScheduledTask::STATUS_PENDING, $scheduledTask->getStatus());
        $this->assertEquals([
            new ScheduledTaskTimePeriod(1, null, 9, 30, $scheduledTask->getTimePeriods()[0]->getId()),
            new ScheduledTaskTimePeriod(28, null, 9, 30, $scheduledTask->getTimePeriods()[1]->getId()),
        ], $scheduledTask->getTimePeriods());

        $this->assertEquals([
            new NotificationGroup(new NotificationGroupSummary("All Friends", [
                new NotificationGroupMember(null, "another@oxil.uk", $newGroup->getMembers()[0]->getId())
            ], NotificationGroup::COMMUNICATION_METHOD_INTERNAL_ONLY, $newGroup->getId()), null, 1)
        ], $alertGroup->getNotificationGroups());


        // Remove alert group
        $this->alertService->deleteAlertGroup($groupId);

        try {
            $this->alertService->getAlertGroup($groupId);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            // As expected
        }

    }


    public function testCanListAlertGroupsOptionallyFilteringAndLimiting() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $notificationGroup = new NotificationGroup(new NotificationGroupSummary("Bobby Brown", [
            new NotificationGroupMember(null, "test@oxil.uk")
        ]), null, 1);
        $notificationGroup->save();

        $alertGroup1 = new AlertGroupSummary("My First Alert Group", [
            new ScheduledTaskTimePeriod(4, null, 13, 30),
            new ScheduledTaskTimePeriod(6, null, 13, 30),
        ], [
            $notificationGroup->returnSummary()
        ]);

        $group1Id = $this->alertService->saveAlertGroup($alertGroup1, null, 1);

        $alertGroup2 = new AlertGroupSummary("Another Alert Group", [
            new ScheduledTaskTimePeriod(4, null, 13, 30),
            new ScheduledTaskTimePeriod(6, null, 13, 30),
        ], [
            $notificationGroup->returnSummary()
        ]);

        $group2Id = $this->alertService->saveAlertGroup($alertGroup2, "datasetProject", 1);


        $alertGroup3 = new AlertGroupSummary("Little Alert Group", [
            new ScheduledTaskTimePeriod(4, null, 13, 30),
            new ScheduledTaskTimePeriod(6, null, 13, 30),
        ], [
            $notificationGroup->returnSummary()
        ]);

        $group3Id = $this->alertService->saveAlertGroup($alertGroup3, "datasetProject", 1);

        // All groups
        $allGroups = $this->alertService->listAlertGroups("", 25, 0, null, 1);
        $this->assertEquals(3, sizeof($allGroups));

        $this->assertEquals(AlertGroup::fetch($group2Id)->returnSummary(), $allGroups[0]);
        $this->assertEquals(AlertGroup::fetch($group3Id)->returnSummary(), $allGroups[1]);
        $this->assertEquals(AlertGroup::fetch($group1Id)->returnSummary(), $allGroups[2]);


        $limited = $this->alertService->listAlertGroups("", 2, 0, null, 1);
        $this->assertEquals(2, sizeof($limited));

        $this->assertEquals(AlertGroup::fetch($group2Id)->returnSummary(), $limited[0]);
        $this->assertEquals(AlertGroup::fetch($group3Id)->returnSummary(), $limited[1]);


        $limited = $this->alertService->listAlertGroups("", 3, 1, null, 1);
        $this->assertEquals(2, sizeof($limited));

        $this->assertEquals(AlertGroup::fetch($group3Id)->returnSummary(), $limited[0]);
        $this->assertEquals(AlertGroup::fetch($group1Id)->returnSummary(), $limited[1]);


        $limited = $this->alertService->listAlertGroups("tle", 25, 0, null, 1);
        $this->assertEquals(1, sizeof($limited));
        $this->assertEquals(AlertGroup::fetch($group3Id)->returnSummary(), $limited[0]);


        $limited = $this->alertService->listAlertGroups("", 25, 0, "datasetProject", 1);
        $this->assertEquals(2, sizeof($limited));
        $this->assertEquals(AlertGroup::fetch($group2Id)->returnSummary(), $limited[0]);
        $this->assertEquals(AlertGroup::fetch($group3Id)->returnSummary(), $limited[1]);

    }

}