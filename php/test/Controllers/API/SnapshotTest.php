<?php

namespace Kinintel\Test\Controllers\API;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Query\Filter\LikeFilter;
use Kinintel\Controllers\API\Snapshot;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\DataProcessor\Snapshot\SnapshotDescriptor;
use Kinintel\ValueObjects\DataProcessor\Snapshot\SnapshotItem;
use League\Uri\Contracts\DataPathInterface;

include_once "autoloader.php";

class SnapshotTest extends TestBase {

    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;

    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * @var Snapshot
     */
    private $snapshot;


    public function setUp(): void {
        $this->dataProcessorService = MockObjectProvider::instance()->getMockInstance(DataProcessorService::class);
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->snapshot = new Snapshot($this->datasetService, $this->dataProcessorService);
    }

    public function testCanGetSnaphotSummariesWhenListingSnapshotsForManagementKey() {

        $this->datasetService->returnValue("getDatasetInstanceByManagementKey",
            new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"),
            ["testmanagement"]);


        $instances = [
            new DataProcessorInstance("example1", "Example 1", "tabulardatasetsnapshot", ["property1" => "test"], DataProcessorInstance::TRIGGER_ADHOC, null, "DatasetInstance", 38),
            new DataProcessorInstance("example2", "Example 2", "tabulardatasetincrementalsnapshot", ["property2" => "test2"], DataProcessorInstance::TRIGGER_SCHEDULED, new ScheduledTask(new ScheduledTaskSummary("test", "Test", [], [], ScheduledTask::STATUS_COMPLETED, "2028-01-01 10:00:00", null, "2020-01-01 10:00:00")), "DatasetInstance", 38)
        ];


        $this->dataProcessorService->returnValue("filterDataProcessorInstances", $instances, [
            ["type" =>
                new LikeFilter("type", "%snapshot%"),
                "relatedObjectType" => "DatasetInstance",
                "relatedObjectKey" => 38], null, 0, 1000000
        ]);


        $snapshotItems = $this->snapshot->listSnapshotsForManagementKey("testmanagement");
        $this->assertEquals([
            new SnapshotItem("example1", "Example 1", SnapshotItem::STANDARD_SNAPSHOT, ["property1" => "test"], DataProcessorInstance::TRIGGER_ADHOC, "Test", "testmanagement", ScheduledTask::STATUS_PENDING, null, null),
            new SnapshotItem("example2", "Example 2", SnapshotItem::INCREMENTAL_SNAPSHOT, ["property2" => "test2"], DataProcessorInstance::TRIGGER_SCHEDULED, "Test", "testmanagement", ScheduledTask::STATUS_COMPLETED, "01/01/2020 10:00:00", "01/01/2028 10:00:00")
        ], $snapshotItems);

    }


    public function testCanCreateSimpleNewAdhocSnapshotForManagementKeyWithTitle() {

        $this->datasetService->returnValue("getFullDataSetInstanceByManagementKey",
            new DatasetInstance(new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"), 3, "helloWorld"),
            ["testmanagement"]);


        $expectedInstance = new DataProcessorInstance("tabulardatasetsnapshot_3_" . date("U"), "My first one", "tabulardatasetsnapshot",
            new TabularDatasetSnapshotProcessorConfiguration([], [], [], true, false), DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "tabulardatasetsnapshot_3_" . date("U"), [
                "dataProcessorKey" => "tabulardatasetsnapshot_3_" . date("U")
            ], []), "helloWorld", 3),
            DataProcessorInstance::RELATED_OBJECT_TYPE_DATASET_INSTANCE, 38, "helloWorld", 3);


        $this->dataProcessorService->returnValue("saveDataProcessorInstance", "tabulardatasetsnapshot_3_" . date("U"), [
            $expectedInstance
        ]);


        $newKey = $this->snapshot->createSnapshotForManagementKey("testmanagement", new SnapshotDescriptor("My first one"));

        $this->assertEquals("tabulardatasetsnapshot_3_" . date("U"), $newKey);

    }


    public function testCanCreateSimpleNewAdhocSnapshotForManagementKeyWithTitleAndParams() {

        $this->datasetService->returnValue("getFullDataSetInstanceByManagementKey",
            new DatasetInstance(new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"), 3, "helloWorld"),
            ["testmanagement"]);


        $expectedInstance = new DataProcessorInstance("tabulardatasetsnapshot_3_" . date("U"), "My first one", "tabulardatasetsnapshot",
            new TabularDatasetSnapshotProcessorConfiguration([], [], ["param1" => "Bernard", "param2" => "Bingo"], true, false), DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "tabulardatasetsnapshot_3_" . date("U"), [
                "dataProcessorKey" => "tabulardatasetsnapshot_3_" . date("U")
            ], []), "helloWorld", 3),
            DataProcessorInstance::RELATED_OBJECT_TYPE_DATASET_INSTANCE, 38, "helloWorld", 3);


        $this->dataProcessorService->returnValue("saveDataProcessorInstance", "tabulardatasetsnapshot_3_" . date("U"), [
            $expectedInstance
        ]);


        $newKey = $this->snapshot->createSnapshotForManagementKey("testmanagement", new SnapshotDescriptor("My first one", ["param1" => "Bernard", "param2" => "Bingo"]));

        $this->assertEquals("tabulardatasetsnapshot_3_" . date("U"), $newKey);

    }


    public function testCanUpdateExistingSnapshotForManagementKey() {

        $this->datasetService->returnValue("getDatasetInstanceByManagementKey",
            new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"),
            ["testmanagement"]);

        $instances = [
            new DataProcessorInstance("example1", "Example 1", "tabulardatasetsnapshot", ["property1" => "test"], DataProcessorInstance::TRIGGER_ADHOC, null, "DatasetInstance", 38),
            new DataProcessorInstance("existing-data-processor", "Example 2", "tabulardatasetincrementalsnapshot", ["property2" => "test2"], DataProcessorInstance::TRIGGER_SCHEDULED, new ScheduledTask(new ScheduledTaskSummary("test", "Test", [], [], ScheduledTask::STATUS_COMPLETED, "2028-01-01 10:00:00", null, "2020-01-01 10:00:00")), "DatasetInstance", 38)
        ];


        $this->dataProcessorService->returnValue("filterDataProcessorInstances", $instances, [
            ["type" =>
                new LikeFilter("type", "%snapshot%"),
                "relatedObjectType" => "DatasetInstance",
                "relatedObjectKey" => 38], null, 0, 1000000
        ]);


        $existingDataProcessor = new DataProcessorInstance("existing-data-processor", "My first one", "tabulardatasetsnapshot",
            new TabularDatasetSnapshotProcessorConfiguration([], [], ["param1" => "Bernard", "param2" => "Bingo"], true, false), DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "existing-data-processor", [
                "dataProcessorKey" => "existing-data-processor"
            ], []), "helloWorld", 3),
            DataProcessorInstance::RELATED_OBJECT_TYPE_DATASET_INSTANCE, 38, "helloWorld", 3);

        $this->dataProcessorService->returnValue("getDataProcessorInstance", $existingDataProcessor, ["existing-data-processor"]);


        $expectedInstance = new DataProcessorInstance("existing-data-processor", "Updated Title", "tabulardatasetsnapshot",
            new TabularDatasetSnapshotProcessorConfiguration([], [], ["param1" => "Hello"], true, false), DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "existing-data-processor", [
                "dataProcessorKey" => "existing-data-processor"
            ], []), "helloWorld", 3),
            DataProcessorInstance::RELATED_OBJECT_TYPE_DATASET_INSTANCE, 38, "helloWorld", 3);


        $this->dataProcessorService->returnValue("saveDataProcessorInstance", "existing-data-processor", [
            $expectedInstance
        ]);


        $key = $this->snapshot->updateSnapshotForManagementKey("testmanagement", "existing-data-processor",
            new SnapshotDescriptor("Updated Title", ["param1" => "Hello"]));

        $this->assertEquals("existing-data-processor", $key);


    }


    public function testSnapshotIsTriggeredIfRunNowFlagSet() {

        $this->datasetService->returnValue("getFullDataSetInstanceByManagementKey",
            new DatasetInstance(new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"), 3, "helloWorld"),
            ["testmanagement"]);


        $this->datasetService->returnValue("getDatasetInstanceByManagementKey",
            new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"),
            ["testmanagement"]);


        $instances = [
            new DataProcessorInstance("tabulardatasetsnapshot_3_" . date("U"), "Example 1", "tabulardatasetsnapshot", ["property1" => "test"], DataProcessorInstance::TRIGGER_ADHOC, null, "DatasetInstance", 38),
            new DataProcessorInstance("existing-data-processor", "Example 2", "tabulardatasetincrementalsnapshot", ["property2" => "test2"], DataProcessorInstance::TRIGGER_SCHEDULED, new ScheduledTask(new ScheduledTaskSummary("test", "Test", [], [], ScheduledTask::STATUS_COMPLETED, "2028-01-01 10:00:00", null, "2020-01-01 10:00:00")), "DatasetInstance", 38)
        ];


        $this->dataProcessorService->returnValue("filterDataProcessorInstances", $instances, [
            ["type" =>
                new LikeFilter("type", "%snapshot%"),
                "relatedObjectType" => "DatasetInstance",
                "relatedObjectKey" => 38], null, 0, 1000000
        ]);

        $expectedInstance = new DataProcessorInstance("tabulardatasetsnapshot_3_" . date("U"), "My first one", "tabulardatasetsnapshot",
            new TabularDatasetSnapshotProcessorConfiguration([], [], [], true, false), DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "tabulardatasetsnapshot_3_" . date("U"), [
                "dataProcessorKey" => "tabulardatasetsnapshot_3_" . date("U")
            ], []), "helloWorld", 3),
            DataProcessorInstance::RELATED_OBJECT_TYPE_DATASET_INSTANCE, 38, "helloWorld", 3);


        $this->dataProcessorService->returnValue("saveDataProcessorInstance", "tabulardatasetsnapshot_3_" . date("U"), [
            $expectedInstance
        ]);


        $newKey = $this->snapshot->createSnapshotForManagementKey("testmanagement", new SnapshotDescriptor("My first one", [], true));

        $this->assertEquals("tabulardatasetsnapshot_3_" . date("U"), $newKey);

        $this->assertTrue($this->dataProcessorService->methodWasCalled("triggerDataProcessorInstance", [
            "tabulardatasetsnapshot_3_" . date("U")
        ]));


    }


    public function testCanTriggerSnapshotForManagementKeyAndSnapshotKey() {

        $this->datasetService->returnValue("getDatasetInstanceByManagementKey",
            new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"),
            ["testmanagement"]);


        $instances = [
            new DataProcessorInstance("mysnapshot", "Example 1", "tabulardatasetsnapshot", ["property1" => "test"], DataProcessorInstance::TRIGGER_ADHOC, null, "DatasetInstance", 38),
            new DataProcessorInstance("existing-data-processor", "Example 2", "tabulardatasetincrementalsnapshot", ["property2" => "test2"], DataProcessorInstance::TRIGGER_SCHEDULED, new ScheduledTask(new ScheduledTaskSummary("test", "Test", [], [], ScheduledTask::STATUS_COMPLETED, "2028-01-01 10:00:00", null, "2020-01-01 10:00:00")), "DatasetInstance", 38)
        ];


        $this->dataProcessorService->returnValue("filterDataProcessorInstances", $instances, [
            ["type" =>
                new LikeFilter("type", "%snapshot%"),
                "relatedObjectType" => "DatasetInstance",
                "relatedObjectKey" => 38], null, 0, 1000000
        ]);

        $this->snapshot->triggerSnapshot("testmanagement", "mysnapshot");

        $this->assertTrue($this->dataProcessorService->methodWasCalled("triggerDataProcessorInstance", [
            "mysnapshot"
        ]));


    }

    public function testCanRemoveSnapshotForManagementKeyAndSnapshotKey() {


        $this->datasetService->returnValue("getDatasetInstanceByManagementKey",
            new DatasetInstanceSummary("Test", "testing", null, [], [], [], null, null, [], 38, null, "testmanagement"),
            ["testmanagement"]);


        $instances = [
            new DataProcessorInstance("mysnapshot", "Example 1", "tabulardatasetsnapshot", ["property1" => "test"], DataProcessorInstance::TRIGGER_ADHOC, null, "DatasetInstance", 38),
            new DataProcessorInstance("existing-data-processor", "Example 2", "tabulardatasetincrementalsnapshot", ["property2" => "test2"], DataProcessorInstance::TRIGGER_SCHEDULED, new ScheduledTask(new ScheduledTaskSummary("test", "Test", [], [], ScheduledTask::STATUS_COMPLETED, "2028-01-01 10:00:00", null, "2020-01-01 10:00:00")), "DatasetInstance", 38)
        ];


        $this->dataProcessorService->returnValue("filterDataProcessorInstances", $instances, [
            ["type" =>
                new LikeFilter("type", "%snapshot%"),
                "relatedObjectType" => "DatasetInstance",
                "relatedObjectKey" => 38], null, 0, 1000000
        ]);

        $this->snapshot->removeSnapshot("testmanagement", "mysnapshot");

        $this->assertTrue($this->dataProcessorService->methodWasCalled("removeDataProcessorInstance", [
            "mysnapshot"
        ]));

    }


}