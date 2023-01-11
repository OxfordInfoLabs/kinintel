<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Project;
use Kiniauth\Objects\MetaData\Category;
use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\ObjectCategory;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Testing\ConcreteClassGenerator;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\MVC\ContentSource\StringContentSource;
use Kinikit\MVC\Response\Download;
use Kinikit\MVC\Response\Headers;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\Dashboard\DashboardSummary;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfile;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSnapshotProfileSummary;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Services\Dataset\Exporter\DatasetExporter;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Test\Services\Dataset\Exporter\TestExporterConfig;
use Kinintel\Test\ValueObjects\Transformation\AnotherTestTransformation;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Exporter\DatasetExporterConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DatasetServiceTest extends TestBase {

    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var MockObject
     */
    private $metaDataService;

    /**
     * @var DatasetService
     */
    private $datasetService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->metaDataService = MockObjectProvider::instance()->getMockInstance(MetaDataService::class);
        $this->datasetService = new DatasetService($this->datasourceService, $this->metaDataService);
    }


    public function testDataSourceDatasetAndTransformationsAreValidatedOnDataSetSave() {

        // Bad datasource
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "badsource");

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["datasourceInstanceKey"]));
        }


        // Bad dataset
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", null, 500);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["datasetInstanceId"]));
        }


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("badtrans")
        ]);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["transformationInstance"]["type"]));
        }


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("Kinintel\ValueObjects\Transformation\TestTransformation", new TestTransformation())
        ]);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["transformationInstances"][0]["config"]["property"]));
        }

    }


    public function testCanSaveRetrieveAndRemoveValidDataSetInstanceForLoggedInUserAndProject() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ])),
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $id = $this->datasetService->saveDataSetInstance($dataSetInstance, 5, 1);

        // Check saved correctly in db
        $dataset = DatasetInstance::fetch($id);
        $this->assertEquals(1, $dataset->getAccountId());
        $this->assertEquals(5, $dataset->getProjectKey());


        $reSet = $this->datasetService->getDataSetInstance($id);
        $this->assertEquals("Test Dataset", $reSet->getTitle());
        $this->assertEquals("test-json", $reSet->getDatasourceInstanceKey());
        $transformationInstance = $reSet->getTransformationInstances()[0];
        $this->assertEquals(new TransformationInstance("filter",
            [
                "filters" => [["lhsExpression" => "property",
                    "rhsExpression" => "foobar",
                    "filterType" => "eq"]],
                "logic" => "AND",
                "filterJunctions" => [],
                "sQLTransformationProcessorKey" => "filter"
            ]
        ), $transformationInstance);


        // Check unserialisation works for transformation instance
        $this->assertEquals(new FilterTransformation([
            new Filter("property", "foobar")
        ]), $transformationInstance->returnTransformation());

        $this->assertEquals([
            new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)
        ], $reSet->getParameters());

        $this->assertEquals([
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ], $reSet->getParameterValues());

        // Remove the data set instance
        $this->datasetService->removeDataSetInstance($id);

        try {
            $this->datasetService->getDataSetInstance($id);
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanSaveValidDatasetInstancesForProjectsAndTags() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ]);


        $tags = [new TagSummary("Project", "My Project", "project"),
            new TagSummary("Account2", "My Account", "account2")];

        $dataSetInstance->setTags($tags);


        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("Project", "My Project", "project"), 2, "soapSuds")),
            new ObjectTag(new Tag(new TagSummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            $tags, 2, "soapSuds"
        ]);

        $id = $this->datasetService->saveDataSetInstance($dataSetInstance, "soapSuds", 2);

        $dataset = DatasetInstance::fetch($id);
        $this->assertEquals(2, $dataset->getAccountId());
        $this->assertEquals("soapSuds", $dataset->getProjectKey());

        $tags = $dataset->getTags();
        $this->assertEquals(2, sizeof($tags));

        $this->assertEquals("account2", $tags[0]->getTag()->getKey());
        $this->assertEquals("project", $tags[1]->getTag()->getKey());


    }


    public function testCanGetFilteredDatasetsForAccountsOptionallyFilteredByProjectAndTagAndCategories() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $categories = [
            new CategorySummary("Account1", "An account wide category available to account 1", "account1")
        ];
        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 1", "Account 1", "account1"), 1)),
        ], [
            $categories, 1, null
        ]);


        $accountDataSet = new DatasetInstanceSummary("Account Dataset", "test-json", null, [], [], [], null, null, $categories);
        $this->datasetService->saveDataSetInstance($accountDataSet, null, 1);

        $accountDataSet = new DatasetInstanceSummary("Second Account Dataset", "test-json");
        $this->datasetService->saveDataSetInstance($accountDataSet, null, 1);


        $datasetProject = new Project("Dataset Project", 1, "datasetProject");
        $datasetProject->save();

        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("Special", "Special Tag", "special"), 1, "datasetProject")),
            new ObjectTag(new Tag(new TagSummary("General", "General Tag", "general"), 1, "datasetProject"))
        ], [
            [
                new TagSummary("Special", "", "special"),
                new TagSummary("General", "", "general")
            ], 1, "datasetProject"
        ]);

        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("General", "General Tag", "general"), 1, "datasetProject"))
        ], [
            [
                new TagSummary("General", "", "general")
            ], 1, "datasetProject"
        ]);


        $projectDataSet = new DatasetInstanceSummary("Project Dataset", "test-json");
        $projectDataSet->setTags([
            new TagSummary("Special", "", "special"),
            new TagSummary("General", "", "general")
        ]);
        $this->datasetService->saveDataSetInstance($projectDataSet, "datasetProject", 1);

        $projectDataSet = new DatasetInstanceSummary("Second Project Dataset", "test-json");
        $projectDataSet->setTags([
            new TagSummary("General", "", "general")
        ]);
        $this->datasetService->saveDataSetInstance($projectDataSet, "datasetProject", 1);


        $filtered = $this->datasetService->filterDataSetInstances("", [], [], null, 0, 10, 1);
        $this->assertEquals(4, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Project Dataset", $filtered[1]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[2]);
        $this->assertEquals("Second Account Dataset", $filtered[2]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[3]);
        $this->assertEquals("Second Project Dataset", $filtered[3]->getTitle());


        // Filter on title
        $filtered = $this->datasetService->filterDataSetInstances("econd", [], [], null, 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Account Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());


        // Filter on categories
        $filtered = $this->datasetService->filterDataSetInstances("", ["account1"], [], null, 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dataset", $filtered[0]->getTitle());

        // Filter on project key
        $filtered = $this->datasetService->filterDataSetInstances("", [], [], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());

        // Filter on tags
        $filtered = $this->datasetService->filterDataSetInstances("", [], ["general"], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());

        $filtered = $this->datasetService->filterDataSetInstances("", [], ["special"], "datasetProject", 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());

        // Filter on special NONE tags
        $filtered = $this->datasetService->filterDataSetInstances("", [], ["NONE"], null, 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Account Dataset", $filtered[1]->getTitle());

        // Offsets and limits
        $filtered = $this->datasetService->filterDataSetInstances("", [], ["general"], "datasetProject", 0, 1, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());


        $filtered = $this->datasetService->filterDataSetInstances("", [], ["general"], "datasetProject", 1, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Project Dataset", $filtered[0]->getTitle());


    }


    public function testCanCreateListUpdateAndRemoveSnapshotProfiles() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSetInstanceSummary = new DatasetInstanceSummary("Test dataset", "test-json", null, [], [], []);
        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, null, 1);

        $snapshotProfile = new DatasetInstanceSnapshotProfileSummary("Daily Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(1, null, 0, 0)
        ]);

        $profileId = $this->datasetService->saveSnapshotProfile($snapshotProfile, $instanceId);
        $this->assertNotNull($profileId);


        /**
         * @var DatasetInstanceSnapshotProfile $snapshotProfile
         */
        $snapshotProfile = DatasetInstanceSnapshotProfile::fetch($profileId);
        $this->assertEquals("Daily Snapshot", $snapshotProfile->getTitle());
        $this->assertEquals($instanceId, $snapshotProfile->getDatasetInstanceId());

        $this->assertNotNull($snapshotProfile->getScheduledTask());
        $this->assertEquals(1, sizeof($snapshotProfile->getScheduledTask()->getTimePeriods()));


        $this->assertNotNull($snapshotProfile->getDataProcessorInstance());
        $processorInstance = $snapshotProfile->getDataProcessorInstance();
        $this->assertEquals("Daily Snapshot", $processorInstance->getTitle());

        // Check we can list correctly
        $profiles = $this->datasetService->listSnapshotProfilesForDataSetInstance($instanceId);
        $this->assertEquals(1, sizeof($profiles));
        $this->assertEquals(DatasetInstanceSnapshotProfile::fetch($profileId)->returnSummary(), $profiles[0]);


        // Create a couple more
        $snapshotProfile = new DatasetInstanceSnapshotProfileSummary("Older Daily Snapshot", "tabulardatasourceimport", [
            "sourceDatasourceKey" => "source",
            "targetDatasources" => [
                [
                    "key" => "target"
                ]
            ]
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(1, null, 0, 0)
        ]);

        $profile2Id = $this->datasetService->saveSnapshotProfile($snapshotProfile, $instanceId);

        $snapshotProfile = new DatasetInstanceSnapshotProfileSummary("Another Daily Snapshot", "tabulardatasourceimport", [
            "sourceDatasourceKey" => "source",
            "targetDatasources" => [
                [
                    "key" => "target"
                ]
            ]
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(1, null, 0, 0)
        ]);

        $profile3Id = $this->datasetService->saveSnapshotProfile($snapshotProfile, $instanceId);

        // Check we can list correctly
        $profiles = $this->datasetService->listSnapshotProfilesForDataSetInstance($instanceId);
        $this->assertEquals(3, sizeof($profiles));
        $this->assertEquals(DatasetInstanceSnapshotProfile::fetch($profile3Id)->returnSummary(), $profiles[0]);
        $this->assertEquals(DatasetInstanceSnapshotProfile::fetch($profileId)->returnSummary(), $profiles[1]);
        $this->assertEquals(DatasetInstanceSnapshotProfile::fetch($profile2Id)->returnSummary(), $profiles[2]);


        // Update a profile
        $updateProfile = $profiles[1];
        $updateProfile->setTitle("Updated title");
        $updateProfile->setTaskTimePeriods([
            new ScheduledTaskTimePeriod(null, 3, 15, 22)
        ]);
        $updateProfile->setProcessorConfig([
            "sourceDatasourceKey" => "source",
            "targetDatasources" => [
                [
                    "key" => "target"
                ]
            ]
        ]);

        $this->datasetService->saveSnapshotProfile($updateProfile, $instanceId);

        $updated = DatasetInstanceSnapshotProfile::fetch($updateProfile->getId());

        $this->assertEquals("Updated title", $updated->getTitle());
        $this->assertEquals(1, sizeof($updated->getScheduledTask()->getTimePeriods()));
        $this->assertEquals(new ScheduledTaskTimePeriod(null, 3, 15, 22, $updated->getScheduledTask()->getTimePeriods()[0]->getId()),
            $updated->getScheduledTask()->getTimePeriods()[0]);
        $this->assertEquals([
            "sourceDatasourceKey" => "source",
            "targetDatasources" => [
                [
                    "key" => "target"
                ]
            ],
            "datasetInstanceId" => $instanceId,
            "snapshotIdentifier" => $updated->getDataProcessorInstance()->getKey()
        ], $updated->getDataProcessorInstance()->getConfig());


        // Remove a snapshot profile
        $this->datasetService->removeSnapshotProfile($instanceId, $profileId);

        try {
            DatasetInstanceSnapshotProfile::fetch($profileId);
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {

        }

    }

    public function testTriggerCanControlScheduledTasks() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSetInstanceSummary = new DatasetInstanceSummary("Test dataset", "test-json", null, [], [], []);
        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, null, 1);

        $snapshotProfileSummary = new DatasetInstanceSnapshotProfileSummary("Summary Adhoc", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_ADHOC);

        $now = (new \DateTime())->format("Y-m-d H:i:s");
        $id = $this->datasetService->saveSnapshotProfile($snapshotProfileSummary, $instanceId);
        $snapshotProfileSummary->setId($id);

        /**
         * @var DatasetInstanceSnapshotProfile $snapshotProfile
         */
        $snapshotProfile = DatasetInstanceSnapshotProfile::fetch($id);


        $this->assertEquals(DatasetInstanceSnapshotProfileSummary::TRIGGER_ADHOC, $snapshotProfile->getTrigger());


        $this->assertEquals(DatasetInstanceSnapshotProfileSummary::TRIGGER_ADHOC, $snapshotProfile->getTrigger());
        $this->assertEquals([], $snapshotProfile->getScheduledTask()->getTimePeriods());


        // Check we can change snapshot to scheduled
        $snapshotProfileSummary->setTrigger(DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE);
        $snapshotProfileSummary->setTaskTimePeriods([new ScheduledTaskTimePeriod(null, 5, 20, 33)]);
        $this->datasetService->saveSnapshotProfile($snapshotProfileSummary, $instanceId);


        /**
         * @var DatasetInstanceSnapshotProfile $snapshotProfile
         */
        $scheduledSnapshotProfile = DatasetInstanceSnapshotProfile::fetch($id);
        /**
         * @var ScheduledTask $task
         */
        $task = $scheduledSnapshotProfile->getScheduledTask();
        $this->assertEquals("dataprocessor", $task->getTaskIdentifier());
        $this->assertEquals(DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, $scheduledSnapshotProfile->getTrigger());
        $this->assertEquals(new ScheduledTaskTimePeriod(null, 5, 20, 33, $scheduledSnapshotProfile->getScheduledTask()->getTimePeriods()[0]->getId()),
            $scheduledSnapshotProfile->getScheduledTask()->getTimePeriods()[0]);


        // Alter this scheduled snapshot
        $snapshotProfileSummary->setTitle("New Title");
        $snapshotProfileSummary->setTaskTimePeriods([new ScheduledTaskTimePeriod(3, null, 4, 1)]);
        $this->datasetService->saveSnapshotProfile($snapshotProfileSummary, $instanceId);

        /**
         * @var DatasetInstanceSnapshotProfile $snapshotProfile
         */
        $newScheduledSnapshotProfile = DatasetInstanceSnapshotProfile::fetch($id);

        $this->assertEquals(DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, $newScheduledSnapshotProfile->getTrigger());
        $this->assertEquals(new ScheduledTaskTimePeriod(3, null, 4, 1, $newScheduledSnapshotProfile->getScheduledTask()->getTimePeriods()[0]->getId()),
            $newScheduledSnapshotProfile->getScheduledTask()->getTimePeriods()[0]);

        // Check we can revert to adhoc
        $snapshotProfileSummary->setTrigger(DatasetInstanceSnapshotProfileSummary::TRIGGER_ADHOC);
        $this->datasetService->saveSnapshotProfile($snapshotProfileSummary, $instanceId);

        /**
         * @var DatasetInstanceSnapshotProfile $snapshotProfile
         */
        $adhocSnapshotProfile = DatasetInstanceSnapshotProfile::fetch($id);

        $this->assertEquals(DatasetInstanceSnapshotProfileSummary::TRIGGER_ADHOC, $adhocSnapshotProfile->getTrigger());
        $this->assertEquals([], $adhocSnapshotProfile->getScheduledTask()->getTimePeriods());
    }


    public function testCanGetFilteredDatasetSnapshotProfiles() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("Project", "My Project", "project"), 2, "soapSuds")),
            new ObjectTag(new Tag(new TagSummary("Account2", "My Account", "account2"), 2, "soapSuds"))
        ], [
            [
                new TagSummary("Project", "My Project", "project"),
                new TagSummary("Account2", "My Account", "account2")
            ], 2, "soapSuds"
        ]);


        $dataSetInstanceSummary = new DatasetInstanceSummary("Test dataset", "test-json", null, [], [], []);
        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, null, 2);

        $dataSetInstanceSummary = new DatasetInstanceSummary("Another dataset", "test-json", null, [], [], []);
        $instance2Id = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, "soapSuds", 2);

        $dataSetInstanceSummary = new DatasetInstanceSummary("Yet Another dataset", "test-json", null, [], [], []);
        $dataSetInstanceSummary->setTags(
            [new TagSummary("Project", "My Project", "project"),
                new TagSummary("Account2", "My Account", "account2")]
        );
        $instance3Id = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, "soapSuds", 2);


        $snapshotProfile1 = new DatasetInstanceSnapshotProfileSummary("Daily Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(null, null, 0, 0)
        ]);

        $snapshotProfile1Id = $this->datasetService->saveSnapshotProfile($snapshotProfile1, $instanceId);


        $snapshotProfile2 = new DatasetInstanceSnapshotProfileSummary("Weekly Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(null, 1, 0, 0)
        ]);


        $snapshotProfile2Id = $this->datasetService->saveSnapshotProfile($snapshotProfile2, $instanceId);


        $snapshotProfile3 = new DatasetInstanceSnapshotProfileSummary("Daily Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(null, null, 0, 0)
        ]);

        $snapshotProfile3Id = $this->datasetService->saveSnapshotProfile($snapshotProfile3, $instance2Id);


        $snapshotProfile4 = new DatasetInstanceSnapshotProfileSummary("Weekly Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(null, 1, 0, 0)
        ]);


        $snapshotProfile4Id = $this->datasetService->saveSnapshotProfile($snapshotProfile4, $instance2Id);


        $snapshotProfile5 = new DatasetInstanceSnapshotProfileSummary("Tagged Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(null, 1, 0, 0)
        ]);


        $snapshotProfile5Id = $this->datasetService->saveSnapshotProfile($snapshotProfile5, $instance3Id);


        $snapshotProfile1 = DatasetInstanceSnapshotProfile::fetch($snapshotProfile1Id);
        $snapshotProfile2 = DatasetInstanceSnapshotProfile::fetch($snapshotProfile2Id);
        $snapshotProfile3 = DatasetInstanceSnapshotProfile::fetch($snapshotProfile3Id);
        $snapshotProfile4 = DatasetInstanceSnapshotProfile::fetch($snapshotProfile4Id);
        $snapshotProfile5 = DatasetInstanceSnapshotProfile::fetch($snapshotProfile5Id);


        // Now check we get back what we are expecting in alphabetical order for an account only query
        $matches = $this->datasetService->filterSnapshotProfiles("", [], null, 0, 10, 2);
        $this->assertEquals(5, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile3), $matches[0]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile4), $matches[1]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile1), $matches[2]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile2), $matches[3]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile5), $matches[4]);


        // Limit to project
        $matches = $this->datasetService->filterSnapshotProfiles("", [], "soapSuds", 0, 10, 2);
        $this->assertEquals(3, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile3), $matches[0]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile4), $matches[1]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile5), $matches[2]);


        // Limit to project and tags
        $matches = $this->datasetService->filterSnapshotProfiles("", ["project"], "soapSuds", 0, 10, 2);
        $this->assertEquals(1, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile5), $matches[0]);

        // Check special NONE tag
        $matches = $this->datasetService->filterSnapshotProfiles("", ["NONE"], null, 0, 10, 2);
        $this->assertEquals(4, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile3), $matches[0]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile4), $matches[1]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile1), $matches[2]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile2), $matches[3]);


        // Limit to filter string
        $matches = $this->datasetService->filterSnapshotProfiles("another", [], null, 0, 10, 2);
        $this->assertEquals(3, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile3), $matches[0]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile4), $matches[1]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile5), $matches[2]);

        $matches = $this->datasetService->filterSnapshotProfiles("daily", [], null, 0, 10, 2);
        $this->assertEquals(2, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile3), $matches[0]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile1), $matches[1]);


        // Offset and limit
        $matches = $this->datasetService->filterSnapshotProfiles("", [], null, 0, 3, 2);
        $this->assertEquals(3, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile3), $matches[0]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile4), $matches[1]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile1), $matches[2]);


        $matches = $this->datasetService->filterSnapshotProfiles("", [], null, 1, 3, 2);
        $this->assertEquals(3, sizeof($matches));
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile4), $matches[0]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile1), $matches[1]);
        $this->assertEquals(new DatasetInstanceSnapshotProfileSearchResult($snapshotProfile2), $matches[2]);
    }


    public function testCanGetFilteredSharedDatasetsWithAccountIdOfNull() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $sharedDataset = new DatasetInstanceSummary("Shared First Dataset", "test-json");
        $this->datasetService->saveDataSetInstance($sharedDataset, null, null);

        $sharedDataset = new DatasetInstanceSummary("Shared Second Dataset", "test-json");
        $this->datasetService->saveDataSetInstance($sharedDataset, null, null);

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $filtered = $this->datasetService->filterDataSetInstances("", [], [], [], 0, 10, null);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertEquals("Shared First Dataset", $filtered[0]->getTitle());
        $this->assertEquals("Shared Second Dataset", $filtered[1]->getTitle());


    }


    public function testCanGetEvaluatedParametersContainingBothDatasourceAndDatasetParams() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $datasetSummary = new DatasetInstanceSummary("My Test", "test-json", null, [], [
            new Parameter("datasetParam1", "Dataset Param 1"), new Parameter("datasetParam2", "Dataset Param 2")
        ]);

        $this->datasourceService->returnValue("getEvaluatedParameters", [
            new Parameter("datasourceParam1", "Datasource Param 1"), new Parameter("datasourceParam2", "Datasource Param 2")
        ], [
            "test-json"
        ]);


        $parameters = $this->datasetService->getEvaluatedParameters($datasetSummary);

        $this->assertEquals([
            new Parameter("datasourceParam1", "Datasource Param 1"), new Parameter("datasourceParam2", "Datasource Param 2"),
            new Parameter("datasetParam1", "Dataset Param 1"), new Parameter("datasetParam2", "Dataset Param 2")
        ], $parameters);


    }


    public function testCanEvaluateDatasourceBasedDatasetUsingSuppliedParamsAndAdditionalTransformations() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $this->datasetService->getEvaluatedDataSetForDataSetInstance($dataSetInstance, ["customParam" => "Hello"], [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "bingo")
            ]))
        ], 10, 30);


        // Check data is merged together and evaluated on data source
        $this->assertTrue($this->datasourceService->methodWasCalled("getEvaluatedDataSource", [
            "test-json",
            ["param1" => "Test",
                "param2" => 44,
                "param3" => true, "customParam" => "Hello"], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "foobar")
                ])),
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "bingo")
                ]))
            ], 10, 30
        ]));

    }


    public function testCanEvaluateDatasetBasedDatasetUsingSuppliedParametersAndAdditionalTransformations() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);
        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstance, null, null);


        $extendedDataSetInstance = new DatasetInstanceSummary("Extended Dataset", null, $instanceId, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")
            ]))
        ], [
            new Parameter("extendedParam", "Extended Parameter")
        ], [
            "extendedParam" => 33
        ]);


        $this->datasetService->getEvaluatedDataSetForDataSetInstance($extendedDataSetInstance, ["customParam" => "Hello"], [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "bingo")
            ]))
        ], 10, 30);


        $converter = Container::instance()->get(ObjectToJSONConverter::class);
        $unconverter = Container::instance()->get(JSONToObjectConverter::class);

        // Check data is merged together and evaluated on data source
        $this->assertTrue($this->datasourceService->methodWasCalled("getEvaluatedDataSource", [
            "test-json",
            ["param1" => "Test",
                "param2" => 44,
                "param3" => true, "extendedParam" => 33, "customParam" => "Hello"],
            [
                new TransformationInstance("filter", $unconverter->convert($converter->convert(new FilterTransformation([
                    new Filter("property", "foobar")
                ])))),
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "pickle")
                ])),
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "bingo")
                ]))
            ], 10, 30
        ]));

    }


    public function testSummaryReturnedReferencingOriginalDatasetIdIfAccountIdNullAndLoggedInAsRegularUser() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [], [], [], "Test Summary", "Test Description", [], "", 25), 1, null);
        $summary = $dataSet->returnSummary();
        $this->assertEquals("test", $summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getId());

        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [], [], [], "Test Summary", "Test Description", [], "", 25), null, null);
        $summary = $dataSet->returnSummary();
        $this->assertEquals("test", $summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getId());


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [], [], [], "Test Summary", "Test Description", [], "", 25), 1, null);
        $summary = $dataSet->returnSummary();
        $this->assertEquals("test", $summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getId());


        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [new TransformationInstance("test", ["bingo" => "hello"])], [new Parameter("customParam", "Custom Parameter")], ["customParam" => "Bob"], "Test Summary", "Test Description", [], "", 25), null, null);
        $summary = $dataSet->returnSummary();
        $this->assertNull($summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getDatasetInstanceId());
        $this->assertEquals(null, $summary->getId());
        $this->assertEquals([], $summary->getTransformationInstances());
        $this->assertEquals([], $summary->getParameters());
        $this->assertEquals(["customParam" => null], $summary->getParameterValues());

    }


    public function testCanGetExtendedDatasetInstanceBasedUponOriginalDatasetInstance() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasetInstanceSummary("Original Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ])),
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $id = $this->datasetService->saveDataSetInstance($dataSetInstance, 5, 1);

        $extended = $this->datasetService->getExtendedDatasetInstance($id);
        $this->assertInstanceOf(DatasetInstanceSummary::class, $extended);
        $this->assertNull($extended->getId());
        $this->assertEquals("Original Dataset Extended", $extended->getTitle());
        $this->assertNull($extended->getDatasourceInstanceKey());
        $this->assertEquals($id, $extended->getDatasetInstanceId());
        $this->assertEquals([], $extended->getParameters());
        $this->assertEquals([], $extended->getTransformationInstances());
        $this->assertEquals([], $extended->getParameterValues());

    }


    public function testSavingDatasetPreservesExistingSnapshots() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasetInstanceSummary("New Dataset For Snapshot", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ])),
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstance, 5, 1);


        // Create and save a snapshot for the new instance.
        $snapshotProfile = new DatasetInstanceSnapshotProfileSummary("Daily Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_SCHEDULE, [
            new ScheduledTaskTimePeriod(1, null, 0, 0)
        ]);


        $profileId = $this->datasetService->saveSnapshotProfile($snapshotProfile, $instanceId);


        /**
         * Sanity check profile saved
         *
         * @var DatasetInstance $reInstance
         */
        $reInstance = DatasetInstance::fetch($instanceId);
        $this->assertEquals(1, sizeof($reInstance->getSnapshotProfiles()));
        $this->assertEquals($profileId, $reInstance->getSnapshotProfiles()[0]->getId());


        // Now resave the dataset
        $reSummary = DatasetInstance::fetch($instanceId)->returnSummary();
        $this->datasetService->saveDataSetInstance($reSummary, null, 1);

        /**
         * @var DatasetInstance $reInstance
         */
        $reInstance = DatasetInstance::fetch($instanceId);
        $this->assertEquals(1, sizeof($reInstance->getSnapshotProfiles()));
        $this->assertEquals($profileId, $reInstance->getSnapshotProfiles()[0]->getId());

    }


    public function testCanUpdateMetaDataForADatasetInstance() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasetInstanceSummary("New Dataset For Snapshot", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ])),
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstance, null, 1);


        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 1", "Account 1", "account1"), 1)),
        ], [
            [new CategorySummary("Account 1", "Account 1", "account1")], 1, null
        ]);


        $this->datasetService->updateDataSetMetaData(new DatasetInstanceSearchResult($instanceId, "Updated Dataset", "New Summary", "New description", [
            new CategorySummary("Account 1", "Account 1", "account1")
        ]));


        $dashboard = $this->datasetService->getDataSetInstance($instanceId);
        $this->assertEquals("Updated Dataset", $dashboard->getTitle());
        $this->assertEquals("New Summary", $dashboard->getSummary());
        $this->assertEquals("New description", $dashboard->getDescription());
        $this->assertEquals([new CategorySummary("Account1", "An account wide category available to account 1", "account1")], $dashboard->getCategories());


    }


    public function testCanGetInUseDashboardCategories() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $accountCategories = [
            new CategorySummary("Account2", "An account wide category available to account 2", "account2")
        ];

        $projectCategories = [
            new CategorySummary("Project", "A project level category available to just one project", "project"),
        ];


        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            $accountCategories, 2, null
        ]);

        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Project", "Project", "project"), 2, "soapSuds")),
        ], [
            $projectCategories, 2, "soapSuds"
        ]);


        $accountDatasetInstance = new DatasetInstanceSummary("Account Dashboard", "test-json", null, [], [], [], "Account Dashboard Summary", "Account Dashboard Description", $accountCategories);
        $this->datasetService->saveDataSetInstance($accountDatasetInstance, null, 2);

        $projectDatasetInstance = new DatasetInstanceSummary("Project Dashboard", "test-json", null, [], [], [], "Project Dashboard Summary", "Project Dashboard Description", $projectCategories);
        $this->datasetService->saveDataSetInstance($projectDatasetInstance, "soapSuds", 2);

        $this->metaDataService->returnValue("getMultipleCategoriesByKey", array_merge($accountCategories, $projectCategories), [
            ["", "account2", "project"], null, 2
        ]);

        $this->metaDataService->returnValue("getMultipleCategoriesByKey", $projectCategories, [
            ["", "project"], "soapSuds", 2
        ]);


        $this->assertEquals(array_merge($accountCategories, $projectCategories), $this->datasetService->getInUseDatasetInstanceCategories([], null, 2));
        $this->assertEquals($projectCategories, $this->datasetService->getInUseDatasetInstanceCategories([], "soapSuds", 2));

    }


    public function testCanGetDatasetByTitleAndOptionallyByAccountAndProject() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $topLevel = new DatasetInstanceSummary("Top Level Dataset", "test-json");
        $topLevelId = $this->datasetService->saveDataSetInstance($topLevel, null, null);

        $account1 = new DatasetInstanceSummary("Account Dataset 1", "test-json");
        $account1Id = $this->datasetService->saveDataSetInstance($account1, null, 1);

        $account2 = new DatasetInstanceSummary("Account Dataset 2", "test-json");
        $account2Id = $this->datasetService->saveDataSetInstance($account2, null, 2);

        $project1 = new DatasetInstanceSummary("Project Dataset 1", "test-json");
        $project1Id = $this->datasetService->saveDataSetInstance($project1, "soapSuds", 2);

        $project2 = new DatasetInstanceSummary("Project Dataset 2", "test-json");
        $project2Id = $this->datasetService->saveDataSetInstance($project2, "wiperBlades", 2);

        $this->assertEquals($this->datasetService->getDataSetInstance($topLevelId), $this->datasetService->getDataSetInstanceByTitle("Top Level Dataset", null, null));
        $this->assertEquals($this->datasetService->getDataSetInstance($account1Id), $this->datasetService->getDataSetInstanceByTitle("Account Dataset 1", null, 1));
        $this->assertEquals($this->datasetService->getDataSetInstance($account2Id), $this->datasetService->getDataSetInstanceByTitle("Account Dataset 2", null, 2));
        $this->assertEquals($this->datasetService->getDataSetInstance($project1Id), $this->datasetService->getDataSetInstanceByTitle("Project Dataset 1", "soapSuds", 2));
        $this->assertEquals($this->datasetService->getDataSetInstance($project2Id), $this->datasetService->getDataSetInstanceByTitle("Project Dataset 2", "wiperBlades", 2));


    }

    public function testCanTriggerSnapshotOnDemand() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSetInstanceSummary = new DatasetInstanceSummary("Test dataset", "test-json", null, [], [], []);
        $datasetInstanceId = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, null, 1);

        $snapshotProfileSummary = new DatasetInstanceSnapshotProfileSummary("Daily Snapshot", "tabulardatasetsnapshot", [
        ], DatasetInstanceSnapshotProfileSummary::TRIGGER_ADHOC);

        $snapshotProfileId = $this->datasetService->saveSnapshotProfile($snapshotProfileSummary, $datasetInstanceId);

        /**
         * @var DatasetInstanceSnapshotProfile $reSnapshot
         */
        $reSnapshot = DatasetInstanceSnapshotProfile::fetch($snapshotProfileId);
        $this->assertNull($reSnapshot->getScheduledTask()->getNextStartTime());

        // Set scheduled task to completed so we see the status change at the end
        $reSnapshot->getScheduledTask()->setStatus(ScheduledTask::STATUS_COMPLETED);
        $reSnapshot->getScheduledTask()->save();

        $this->datasetService->triggerSnapshot($datasetInstanceId, $snapshotProfileId);

        /**
         * @var DatasetInstanceSnapshotProfile $triggeredProfile
         */
        $triggeredProfile = DatasetInstanceSnapshotProfile::fetch($snapshotProfileId);

        $nextStartTime = $triggeredProfile->getScheduledTask()->getNextStartTime();
        $this->assertTrue(date("U") - $nextStartTime->format("U") >= 0);
        $this->assertEquals(ScheduledTask::STATUS_PENDING, $triggeredProfile->getScheduledTask()->getStatus());


    }

    public function testCanExportDatasetUsingSuppliedExporterKeyAndValidConfiguration() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)]);
        $this->datasetService->saveDataSetInstance($dataSetInstance, null, null);


        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Bob", "age" => 22],
            ["name" => "Mary", "age" => 32],
            ["name" => "Jane", "age" => 45]
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSource",
            $dataset,
            [
                "test-json",
                ["param1" => "Test",
                    "param2" => 44,
                    "param3" => true],
                [
                    new TransformationInstance("filter", new FilterTransformation([
                        new Filter("property", "foobar")
                    ])),
                    new TransformationInstance("filter", new FilterTransformation([
                        new Filter("property", "pickle")
                    ]))
                ], 10, 30
            ]);

        $exportConfig = new TestExporterConfig("Test", 11);
        $mockExporter = MockObjectProvider::instance()->getMockInstance(DatasetExporter::class);
        Container::instance()->addInterfaceImplementation(DatasetExporter::class, "test", get_class($mockExporter));
        Container::instance()->set(get_class($mockExporter), $mockExporter);

        $mockExporter->returnValue("getConfigClass", TestExporterConfig::class);
        $mockExporter->returnValue("getDownloadFileExtension", "test", [$exportConfig]);
        $mockExporter->returnValue("validateConfig", $exportConfig, [$exportConfig]);

        $mockExporter->returnValue("exportDataset",
            new StringContentSource("HELLO WORLD"),
            [
                $dataset, $exportConfig
            ]);


        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")]))], 10, 30);

        $headers = [
            Headers::HEADER_CACHE_CONTROL => "public, max-age=0"
        ];


        $this->assertEquals(new Download(new StringContentSource("HELLO WORLD"), "test_dataset-" . date("U") . ".test", 200, $headers), $response);


        // Check none download one.
        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")]))], 10, 30, false);


        $this->assertEquals(new SimpleResponse(new StringContentSource("HELLO WORLD"), 200, $headers), $response);

        // Check different caching time
        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")]))], 10, 30, true, 200);


        $expected = new Download(new StringContentSource("HELLO WORLD"), "test_dataset-" . date("U") . ".test", 200,
            [
                Headers::HEADER_CACHE_CONTROL => "public, max-age=200"
            ]);
        $this->assertEquals($expected, $response);

        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")]))], 10, 30, false, 120);


        $expected = new SimpleResponse(new StringContentSource("HELLO WORLD"), 200, [
            Headers::HEADER_CACHE_CONTROL => "public, max-age=120"
        ]);

        $this->assertEquals($expected, $response);


    }


    public function testIfInvalidExportConfigurationSuppliedExceptionRaised() {

        $mockExporter = MockObjectProvider::instance()->getMockInstance(DatasetExporter::class);
        Container::instance()->addInterfaceImplementation(DatasetExporter::class, "test", get_class($mockExporter));
        Container::instance()->set(get_class($mockExporter), $mockExporter);

        $mockExporter->returnValue("getConfigClass", TestExporterConfig::class);


        $mockExporter->throwException("validateConfig", new ValidationException([]), [new TestExporterConfig()]);

        try {
            $this->datasetService->exportDatasetInstance(null, "test", new TestExporterConfig());
            $this->fail("should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }


    }

}