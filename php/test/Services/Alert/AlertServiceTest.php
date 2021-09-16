<?php

namespace Kinintel\Test\Services\Alert;

use GuzzleHttp\Handler\Proxy;
use Kiniauth\Objects\Communication\Notification\NotificationGroup;
use Kiniauth\Objects\Communication\Notification\NotificationGroupMember;
use Kiniauth\Objects\Communication\Notification\NotificationGroupSummary;
use Kiniauth\Objects\Communication\Notification\NotificationLevel;
use Kiniauth\Objects\Communication\Notification\NotificationSummary;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Communication\Notification\NotificationService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\TemplateParser;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Alert\AlertGroup;
use Kinintel\Objects\Alert\AlertGroupSummary;
use Kinintel\Objects\Alert\AlertGroupTimePeriod;
use Kinintel\Objects\Dashboard\DashboardDatasetInstance;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Alert\AlertService;
use Kinintel\Services\Dashboard\DashboardService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Alert\ActiveDashboardDatasetAlerts;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class AlertServiceTest extends TestBase {

    /**
     * @var AlertService
     */
    private $alertService;

    /**
     * @var MockObject
     */
    private $dashboardService;


    /**
     * @var MockObject
     */
    private $notificationService;


    /**
     * @var MockObject
     */
    private $templateParser;

    /**
     * Setup function
     */
    public function setUp(): void {
        $this->dashboardService = MockObjectProvider::instance()->getMockInstance(DashboardService::class);
        $this->notificationService = MockObjectProvider::instance()->getMockInstance(NotificationService::class);
        $this->templateParser = MockObjectProvider::instance()->getMockInstance(TemplateParser::class);
        $this->alertService = new AlertService($this->dashboardService, $this->notificationService, $this->templateParser);
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
        ], "Daily Alerts", "Welcome to daily alerts", "Thanks for listening",
            NotificationLevel::fetch("warning"));


        $groupId = $this->alertService->saveAlertGroup($alertGroup, null, 1);
        $this->assertNotNull($groupId);


        /**
         * @var AlertGroup $alertGroup
         */
        $alertGroup = AlertGroup::fetch($groupId);

        $this->assertEquals("My Alert Group", $alertGroup->getTitle());
        $this->assertEquals("Daily Alerts", $alertGroup->getNotificationTitle());
        $this->assertEquals("Welcome to daily alerts", $alertGroup->getNotificationPrefixText());
        $this->assertEquals("Thanks for listening", $alertGroup->getNotificationSuffixText());
        $this->assertEquals(NotificationLevel::fetch("warning"), $alertGroup->getNotificationLevel());

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
        $reAlertGroup->setNotificationTitle("Updated Daily Alerts");
        $reAlertGroup->setNotificationPrefixText("Catch me if you can");
        $reAlertGroup->setNotificationSuffixText("Another day in paradise");

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
        $this->assertEquals("Updated Daily Alerts", $alertGroup->getNotificationTitle());
        $this->assertEquals("Catch me if you can", $alertGroup->getNotificationPrefixText());
        $this->assertEquals("Another day in paradise", $alertGroup->getNotificationSuffixText());

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


    public function testNotificationGeneratedWithCombinedTextWhenProcessingAlertGroupWithFiringAlerts() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        $notificationGroup = new NotificationGroup(new NotificationGroupSummary("Test Notification Group"), null, 1);
        $notificationGroup->save();

        $alertGroup = new AlertGroupSummary("Test Alert Group", [], [
            $notificationGroup->returnSummary()
        ], "Test Alert", "Please see alerts below",
            "Thanks for your support", NotificationLevel::fetch("warning"));

        $alertGroupId = $this->alertService->saveAlertGroup($alertGroup, null, 1);

        $dashboardDataset1 = new DashboardDatasetInstance("testdataset1", null, null, [], [], 5);
        $dashboardDataset2 = new DashboardDatasetInstance("testdataset2", null, null, [], [], 6);


        $this->dashboardService->returnValue("getActiveDashboardDatasetAlertsMatchingAlertGroup",
            [
                new ActiveDashboardDatasetAlerts($dashboardDataset1, [
                    new Alert("First Alert", "rowcount", ["matchType" => "equals", "value" => 0], null,
                        "No rows match", $alertGroupId),
                    new Alert("Second Alert", "rowcount", ["matchType" => "equals", "value" => 1], null,
                        "Has matching rows", $alertGroupId)]),
                new ActiveDashboardDatasetAlerts($dashboardDataset2, [
                    new Alert("Third Alert", "rowcount", ["matchType" => "equals", "value" => 0], null,
                        "No other rows match", $alertGroupId),
                    new Alert("Fourth Alert", "rowcount", ["matchType" => "equals", "value" => 1], null,
                        "Has other matching rows", $alertGroupId)]),

            ], [$alertGroupId]);


        // Program no results for both datasets
        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("Data")], []), [
                $dashboardDataset1, []
            ]);

        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("Data")], []), [
                $dashboardDataset2, []
            ]);

        $this->templateParser->returnValue("parseTemplateText", "No rows match", [
            "No rows match", ["rowCount" => 0, "data" => []]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "No other rows match", [
            "No other rows match", ["rowCount" => 0, "data" => []]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "Has matching rows", [
            "Has matching rows", ["rowCount" => 1, "data" => [["data" => "Pingu"]]]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "Has other matching rows", [
            "Has other matching rows", ["rowCount" => 1, "data" => [["data" => "Bing"]]]
        ]);


        // Process the group
        $this->alertService->processAlertGroup($alertGroupId);

        $expectedNotification = new NotificationSummary("Test Alert", "Please see alerts below\nNo rows match\nNo other rows match\nThanks for your support", null,
            [$notificationGroup], null, NotificationLevel::fetch("warning")
        );

        // Check notification was created with correct data
        $this->assertTrue($this->notificationService->methodWasCalled("createNotification", [
            $expectedNotification, null, 1
        ]));


        // Now change the return value and check other items fired
        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("data")], [
                ["data" => "Pingu"]
            ]), [
                $dashboardDataset1, []
            ]);

        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("data")], [["data" => "Bing"]]), [
                $dashboardDataset2, []
            ]);


        // Process the group
        $this->alertService->processAlertGroup($alertGroupId);

        $expectedNotification = new NotificationSummary("Test Alert", "Please see alerts below\nHas matching rows\nHas other matching rows\nThanks for your support", null,
            [$notificationGroup], null, NotificationLevel::fetch("warning")
        );


        // Check notification was created with correct data
        $this->assertTrue($this->notificationService->methodWasCalled("createNotification", [
            $expectedNotification, null, 1
        ]));

    }


    public function testTemplatedAlertMessagesAreEvaluatedUsingDatasetCountAndDataParams() {

        $notificationGroup = new NotificationGroup(new NotificationGroupSummary("Test Notification Group"), null, 1);
        $notificationGroup->save();

        $alertGroup = new AlertGroupSummary("Test Alert Group", [], [
            $notificationGroup->returnSummary()
        ], "Test Alert", "Please see alerts below",
            "Thanks for your support", NotificationLevel::fetch("warning"));

        $alertGroupId = $this->alertService->saveAlertGroup($alertGroup, null, 1);

        $dashboardDataset1 = new DashboardDatasetInstance("testdataset1", null, null, [], [], 5);

        $this->dashboardService->returnValue("getActiveDashboardDatasetAlertsMatchingAlertGroup",
            [
                new ActiveDashboardDatasetAlerts($dashboardDataset1, [
                    new Alert("Test Alert", "rowcount", ["matchType" => "greater", "value" => 1], null,
                        "UNPARSED TEMPLATE", $alertGroupId)
                ])
            ]);


        // Program no results for both datasets
        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("data")], [[
                "data" => "My item"
            ],
                ["data" => "Your item"]]), [
                $dashboardDataset1, []
            ]);


        $this->templateParser->returnValue("parseTemplateText", "PARSED TEMPLATE", [
            "UNPARSED TEMPLATE", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        // Process the group
        $this->alertService->processAlertGroup($alertGroupId);

        $expectedNotification = new NotificationSummary("Test Alert", "Please see alerts below\nPARSED TEMPLATE\nThanks for your support", null,
            [$notificationGroup], null, NotificationLevel::fetch("warning")
        );


        // Check notification was created with correct data
        $this->assertTrue($this->notificationService->methodWasCalled("createNotification", [
            $expectedNotification, null, 1
        ]));

    }


    public function testAlertsWithAdditionalFilterTransformationAreEvaluatedAsExpected() {

        $notificationGroup = new NotificationGroup(new NotificationGroupSummary("Test Notification Group"), null, 1);
        $notificationGroup->save();

        $alertGroup = new AlertGroupSummary("Test Alert Group", [], [
            $notificationGroup->returnSummary()
        ], "Test Alert", "Please see alerts below",
            "Thanks for your support", NotificationLevel::fetch("warning"));

        $alertGroupId = $this->alertService->saveAlertGroup($alertGroup, null, 1);

        $dashboardDataset1 = new DashboardDatasetInstance("testdataset1", null, null, [], [], 5);

        $this->dashboardService->returnValue("getActiveDashboardDatasetAlertsMatchingAlertGroup",
            [
                new ActiveDashboardDatasetAlerts($dashboardDataset1, [
                    new Alert("Test Alert", "rowcount", ["matchType" => "greater", "value" => 1], new FilterTransformation([
                        new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
                    ]),
                        "UNPARSED TEMPLATE", $alertGroupId)
                ])
            ]);


        // Program no results for both datasets
        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("data")], [[
                "data" => "My item"
            ],
                ["data" => "Your item"]]), [
                $dashboardDataset1, [new TransformationInstance("filter", new FilterTransformation([
                    new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
                ]))]
            ]);


        $this->templateParser->returnValue("parseTemplateText", "PARSED TEMPLATE", [
            "UNPARSED TEMPLATE", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        // Process the group
        $this->alertService->processAlertGroup($alertGroupId);

        $expectedNotification = new NotificationSummary("Test Alert", "Please see alerts below\nPARSED TEMPLATE\nThanks for your support", null,
            [$notificationGroup], null, NotificationLevel::fetch("warning")
        );


        // Check notification was created with correct data
        $this->assertTrue($this->notificationService->methodWasCalled("createNotification", [
            $expectedNotification, null, 1
        ]));


    }

    public function testCanEvaluateAllAlertsForADashboardDataSet() {

        $dashboardDataset1 = new DashboardDatasetInstance("testdataset1", null, null, [], [
            new Alert("First Alert", "rowcount", ["matchType" => "greater", "value" => 0], new FilterTransformation([
                new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
            ]),
                "UNPARSED TEMPLATE", "UNPARSED SUMMARY TEMPLATE"),
            new Alert("Second Alert", "rowcount", ["matchType" => "greater", "value" => 1], new FilterTransformation([
                new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
            ]),
                "UNPARSED TEMPLATE 2", "UNPARSED SUMMARY TEMPLATE 2"),
        ]);


        $this->templateParser->returnValue("parseTemplateText", "PARSED TEMPLATE", [
            "UNPARSED TEMPLATE", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "PARSED TEMPLATE 2", [
            "UNPARSED TEMPLATE 2", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "PARSED SUMMARY TEMPLATE", [
            "UNPARSED SUMMARY TEMPLATE", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "PARSED SUMMARY TEMPLATE 2", [
            "UNPARSED SUMMARY TEMPLATE 2", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);


        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("data")], [[
                "data" => "My item"
            ],
                ["data" => "Your item"]]), [
                $dashboardDataset1, [new TransformationInstance("filter", new FilterTransformation([
                    new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
                ]))]
            ]);


        $processedAlerts = $this->alertService->processAlertsForDashboardDatasetInstance($dashboardDataset1);


        $this->assertEquals(2, sizeof($processedAlerts));
        $this->assertEquals([
            ["alertIndex" => 0, "notificationMessage" => "PARSED TEMPLATE", "summaryMessage" => "PARSED SUMMARY TEMPLATE"],
            ["alertIndex" => 1, "notificationMessage" => "PARSED TEMPLATE 2", "summaryMessage" => "PARSED SUMMARY TEMPLATE 2"]
        ], $processedAlerts);


    }


    public function testCanEvaluateAlertsForSingleAlertOnDashboardDataSet() {

        $dashboardDataset1 = new DashboardDatasetInstance("testdataset1", null, null, [], [
            new Alert("First Alert", "rowcount", ["matchType" => "greater", "value" => 0], new FilterTransformation([
                new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
            ]),
                "UNPARSED TEMPLATE", "UNPARSED SUMMARY TEMPLATE"),
            new Alert("Second Alert", "rowcount", ["matchType" => "greater", "value" => 1], new FilterTransformation([
                new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
            ]),
                "UNPARSED TEMPLATE 2", "UNPARSED SUMMARY TEMPLATE 2"),
        ]);


        $this->templateParser->returnValue("parseTemplateText", "PARSED TEMPLATE", [
            "UNPARSED TEMPLATE", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "PARSED TEMPLATE 2", [
            "UNPARSED TEMPLATE 2", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "PARSED SUMMARY TEMPLATE", [
            "UNPARSED SUMMARY TEMPLATE", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);

        $this->templateParser->returnValue("parseTemplateText", "PARSED SUMMARY TEMPLATE 2", [
            "UNPARSED SUMMARY TEMPLATE 2", [
                "rowCount" => 2,
                "data" => [[
                    "data" => "My item"
                ],
                    ["data" => "Your item"]]
            ]
        ]);


        $this->dashboardService->returnValue("getEvaluatedDataSetForDashboardDataSetInstanceObject",
            new ArrayTabularDataset([new Field("data")], [[
                "data" => "My item"
            ],
                ["data" => "Your item"]]), [
                $dashboardDataset1, [new TransformationInstance("filter", new FilterTransformation([
                    new Filter("test", "5", Filter::FILTER_TYPE_GREATER_THAN)
                ]))]
            ]);


        $processedAlerts = $this->alertService->processAlertsForDashboardDatasetInstance($dashboardDataset1, 0);


        $this->assertEquals(1, sizeof($processedAlerts));
        $this->assertEquals([
            ["alertIndex" => 0, "summaryMessage" => "PARSED SUMMARY TEMPLATE", "notificationMessage" => "PARSED TEMPLATE"],
        ], $processedAlerts);


        $processedAlerts = $this->alertService->processAlertsForDashboardDatasetInstance($dashboardDataset1, 1);


        $this->assertEquals(1, sizeof($processedAlerts));
        $this->assertEquals([
            ["alertIndex" => 1, "summaryMessage" => "PARSED SUMMARY TEMPLATE 2", "notificationMessage" => "PARSED TEMPLATE 2"],
        ], $processedAlerts);


    }


}