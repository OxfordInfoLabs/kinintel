<?php


namespace Kinintel\Test\Services\DataProcessor;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Account\AccountService;
use Kiniauth\Services\Security\ActiveRecordInterceptor;
use Kiniauth\Services\Security\SecurityService;
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
     * Set up method
     */
    public function setUp(): void {
        $this->dataProcessorDao = MockObjectProvider::instance()->getMockInstance(DataProcessorDAO::class);
        $this->securityService = MockObjectProvider::instance()->getMockInstance(SecurityService::class);
        $this->accountService = MockObjectProvider::instance()->getMockInstance(AccountService::class);
        $this->activeRecordInterceptor = MockObjectProvider::instance()->getMockInstance(ActiveRecordInterceptor::class);

        $this->dataProcessorService = new DataProcessorService($this->dataProcessorDao, Container::instance()->get(ObjectBinder::class),
            Container::instance()->get(Validator::class), $this->securityService, $this->accountService, $this->activeRecordInterceptor);
    }


    public function testInvalidDataProcessorTypeExceptionRaisedIfBadTypeSupplied() {

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


    public function testInvalidDataProcessorConfigExceptionRaisedIfConfigFailsValidation() {

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


    public function testIfAccountLevelInstanceSystemIsLoggedInAsAccount() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "testprocessor", [
                "property" => "TESTING"
            ], null, 25), [
                "bigone"
            ]);

        $account = new Account("TESTING1234");
        $this->accountService->returnValue("getAccount", $account, [25]);


        Container::instance()->addInterfaceImplementation(DataProcessor::class, "testprocessor", TestDataProcessor::class);

        $this->dataProcessorService->processDataProcessorInstance("bigone");

        $testProcessor = Container::instance()->get(TestDataProcessor::class);
        $this->assertEquals(new TestDataProcessorConfig("TESTING"), $testProcessor->processedConfig);


        // Execute passed logic to active record interceptor
        $this->activeRecordInterceptor->getMethodCallHistory("executeInsecure")[0][0]();

        $this->assertTrue($this->securityService->methodWasCalled("login", [null, $account]));


    }


    public function testIfNoneAccountLevelInstanceSystemIsLoggedInAsSuperUser() {

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

        $this->assertTrue($this->securityService->methodWasCalled("loginAsSuperUser"));


    }


}