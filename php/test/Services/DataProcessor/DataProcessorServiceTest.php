<?php


namespace Kinintel\Test\Services\DataProcessor;

use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTask;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskSummary;
use Kiniauth\Objects\Workflow\Task\Scheduled\ScheduledTaskTimePeriod;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Services\Workflow\Task\Scheduled\ScheduledTaskService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\Validator;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Exception\InvalidDataProcessorTypeException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\DataProcessor\DataProcessorDAO;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\ValueObjects\DataProcessor\DataProcessorItem;

include_once "autoloader.php";

class DataProcessorServiceTest extends TestBase {


    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;


    /**
     * @var MockObject
     */
    private $dataProcessorDao;


    /**
     * @var MockObject
     */
    private $securityService;

    /**
     * @var MockObject
     */
    private $accountService;


    /**
     * @var MockObject
     */
    private $activeRecordInterceptor;


    /**
     * @var MockObject
     */
    private $scheduledTaskService;


    /**
     * Set up method
     */
    public function setUp(): void {
        $this->dataProcessorDao = MockObjectProvider::instance()->getMockInstance(DataProcessorDAO::class);
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
        $this->accountService = MockObjectProvider::instance()->getMockInstance(AccountService::class);
        $this->activeRecordInterceptor = MockObjectProvider::instance()->getMockInstance(ActiveRecordInterceptor::class);
        $this->scheduledTaskService = MockObjectProvider::instance()->getMockInstance(ScheduledTaskService::class);

        $this->dataProcessorService = new DataProcessorService($this->dataProcessorDao, Container::instance()->get(ObjectBinder::class),
            Container::instance()->get(Validator::class), $this->securityService, $this->accountService, $this->activeRecordInterceptor, $this->scheduledTaskService);
    }


    /**
     * @doesNotPerformAssertions
     */
    public function testExceptionRaisedIfUnknownProcessorTypeOrInvalidConfigPassedForNewDataProcessor() {
        $newDataProcessor = (new DataProcessorItem("Bad one", "unknown", []))->toDataProcessorInstance();

        try {
            $this->dataProcessorService->saveDataProcessorInstance($newDataProcessor);
            $this->fail("Should have thrown here");
        } catch (InvalidDataProcessorTypeException $e) {
        }

        $newDataProcessor->setType("multi");
        try {
            $this->dataProcessorService->saveDataProcessorInstance($newDataProcessor);
            $this->fail("Should have thrown here");
        } catch (InvalidDataProcessorConfigException $e) {
        }
    }

    public function testCanSaveSimpleNewValidAdhocDataProcessor() {

        $newDataProcessor = (new DataProcessorItem("Valid", "sqlquery", ["query" => "SELECT * FROM test", "authenticationCredentialsKey" => "test"]))->toDataProcessorInstance("testProject", 1);
        $newKey = $this->dataProcessorService->saveDataProcessorInstance($newDataProcessor);

        $this->assertNotNull($newKey);
        $this->assertStringStartsWith("sqlquery_1_", $newKey);

        $expectedInstance = new DataProcessorInstance($newKey, "Valid", "sqlquery", ["query" => "SELECT * FROM test", "authenticationCredentialsKey" => "test"],
            DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", $newKey, ["dataProcessorKey" => $newKey], []), "testProject", 1), null, null, "testProject", 1);

        $this->assertTrue($this->dataProcessorDao->methodWasCalled("saveProcessorInstance", [
            $expectedInstance
        ]));

    }


    public function testCanCreateValidScheduledProcessor() {
        $newDataProcessor = (new DataProcessorItem("Valid", "sqlquery", ["query" => "SELECT * FROM test", "authenticationCredentialsKey" => "test"], DataProcessorInstance::TRIGGER_SCHEDULED, null, null, null, [
            new ScheduledTaskTimePeriod("20", "3", "10", "30"),
            new ScheduledTaskTimePeriod(null, null, "11", "20")
        ]))->toDataProcessorInstance("testProject", 1);
        $newKey = $this->dataProcessorService->saveDataProcessorInstance($newDataProcessor, "testProject", 1);

        $this->assertNotNull($newKey);
        $this->assertStringStartsWith("sqlquery_1_", $newKey);

        $expectedInstance = new DataProcessorInstance($newKey, "Valid", "sqlquery", ["query" => "SELECT * FROM test", "authenticationCredentialsKey" => "test"],
            DataProcessorInstance::TRIGGER_SCHEDULED,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", $newKey, ["dataProcessorKey" => $newKey],
                [
                    new ScheduledTaskTimePeriod("20", "3", "10", "30"),
                    new ScheduledTaskTimePeriod(null, null, "11", "20")
                ]), "testProject", 1), null, null, "testProject", 1);

        $this->assertTrue($this->dataProcessorDao->methodWasCalled("saveProcessorInstance", [
            $expectedInstance
        ]));
    }


    public function testGettingAndFilteringScheduledProcessorsDelegateDirectlyToDAO() {

        $expectedInstance1 = new DataProcessorInstance("jump", "Jump", "sqlquery", ["query" => "SELECT * FROM test", "authenticationCredentialsKey" => "test"]);
        $expectedInstance2 = new DataProcessorInstance("jump2", "Jump 2", "sqlquery", ["query" => "SELECT * FROM test", "authenticationCredentialsKey" => "test"]);
        $expectedInstance3 = new DataProcessorInstance("jump3", "Jump 3", "sqlquery", ["query" => "SELECT * FROM test", "authenticationCredentialsKey" => "test"]);


        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey", $expectedInstance1, ["jump"]);
        $this->assertEquals($expectedInstance1, $this->dataProcessorService->getDataProcessorInstance("jump"));

        $this->dataProcessorDao->returnValue("filterDataProcessorInstances", [$expectedInstance1, $expectedInstance2, $expectedInstance3], [
            ["title" => "Bingo"], "myProject", 25, 50, 33
        ]);

        $this->assertEquals([$expectedInstance1, $expectedInstance2, $expectedInstance3], $this->dataProcessorService->filterDataProcessorInstances(["title" => "Bingo"], "myProject", 25, 50, 33));

    }


    public function testCanTriggerDataProcessorInstanceWhenProcessorAttachedInDatabase() {

        $existingItem = new DataProcessorInstance("onetotrigger", "Previous Valid",
            "sqlquery", ["query" => "SELECT * FROM previous_test", "authenticationCredentialsKey" => "test"],
            DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "updatedone",
                ["dataProcessorKey" => "onetotrigger"], [], ScheduledTask::STATUS_COMPLETED, null, "2020-01-01 10:00:00", "2020-01-01 11:00:00", null, 86400, 123), "testProject", 1));

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey", $existingItem, ["onetotrigger"]);

        // Trigger data processor
        $this->dataProcessorService->triggerDataProcessorInstance("onetotrigger");

        // Check scheduled task was triggered
        $this->assertTrue($this->scheduledTaskService->methodWasCalled("triggerScheduledTask", [123]));
    }


    public function testCanRemoveDataProcessorInstance() {


        $existingItem = new DataProcessorInstance("bingo", "Previous Valid",
            "sqlquery", ["query" => "SELECT * FROM previous_test", "authenticationCredentialsKey" => "test"],
            DataProcessorInstance::TRIGGER_ADHOC,
            new ScheduledTask(new ScheduledTaskSummary("dataprocessor", "updatedone",
                ["dataProcessorKey" => "bingo"], [], ScheduledTask::STATUS_COMPLETED, null, "2020-01-01 10:00:00", "2020-01-01 11:00:00", null, 86400, 123), "testProject", 1));

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey", $existingItem, ["bingo"]);


        $this->dataProcessorService->removeDataProcessorInstance("bingo");
        $this->assertTrue($this->dataProcessorDao->methodWasCalled("removeProcessorInstance", ["bingo"]));
    }


    public function testInvalidDataProcessorTypeExceptionRaisedIfBadTypeSuppliedToProcess() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "testbadprocessor", [
                "property" => "TESTING"
            ]), [
                "bigone"
            ]);

        try {
            $this->dataProcessorService->processDataProcessorInstance("bigone");
            $this->fail("Should have thrown here");
        } catch (InvalidDataProcessorTypeException $e) {
            $this->assertTrue(true);
        }
    }


    public function testInvalidDataProcessorConfigExceptionRaisedIfConfigFailsValidationOnProcess() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "testprocessor", []), [
                "bigone"
            ]);

        Container::instance()->addInterfaceImplementation(DataProcessor::class, "testprocessor", TestDataProcessor::class);


        try {
            $this->dataProcessorService->processDataProcessorInstance("bigone");
            $this->fail("Should have thrown here");
        } catch (InvalidDataProcessorConfigException $e) {
            $this->assertTrue(true);
        }
    }


    public function testCanProcessAValidDataProcessorInstance() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "testprocessor", [
                "property" => "TESTING"
            ]), [
                "bigone"
            ]);

        Container::instance()->addInterfaceImplementation(DataProcessor::class, "testprocessor", TestDataProcessor::class);

        $this->dataProcessorService->processDataProcessorInstance("bigone");

        $testProcessor = Container::instance()->get(TestDataProcessor::class);
        $this->assertEquals(new TestDataProcessorConfig("TESTING"), $testProcessor->processedConfig);

    }


    public function testIfAccountLevelInstanceSystemIsLoggedInAsAccountOnProcess() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "testprocessor", [
                "property" => "TESTING"
            ], null, null, null, null, null, 25), [
                "bigone"
            ]);


        Container::instance()->addInterfaceImplementation(DataProcessor::class, "testprocessor", TestDataProcessor::class);

        $this->dataProcessorService->processDataProcessorInstance("bigone");

        $testProcessor = Container::instance()->get(TestDataProcessor::class);
        $this->assertEquals(new TestDataProcessorConfig("TESTING"), $testProcessor->processedConfig);


        // Execute passed logic to active record interceptor
        $this->activeRecordInterceptor->getMethodCallHistory("executeInsecure")[0][0]();

        $this->assertTrue($this->securityService->methodWasCalled("becomeAccount", [25]));


    }


    public function testIfNoneAccountLevelInstanceSystemIsLoggedInAsSuperUserOnProcess() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "testprocessor", [
                "property" => "TESTING"
            ]), [
                "bigone"
            ]);


        Container::instance()->addInterfaceImplementation(DataProcessor::class, "testprocessor", TestDataProcessor::class);

        $this->dataProcessorService->processDataProcessorInstance("bigone");

        $testProcessor = Container::instance()->get(TestDataProcessor::class);
        $this->assertEquals(new TestDataProcessorConfig("TESTING"), $testProcessor->processedConfig);


        // Execute passed logic to active record interceptor
        $this->activeRecordInterceptor->getMethodCallHistory("executeInsecure")[0][0]();

        $this->assertTrue($this->securityService->methodWasCalled("becomeSuperUser"));


    }


}