<?php


namespace Kinintel\Test\Services\DataProcessor;

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
     * Set up method
     */
    public function setUp(): void {
        $this->dataProcessorDao = MockObjectProvider::instance()->getMockInstance(DataProcessorDAO::class);
        $this->dataProcessorService = new DataProcessorService($this->dataProcessorDao, Container::instance()->get(ObjectBinder::class),
            Container::instance()->get(Validator::class));
    }


    public function testInvalidDataProcessorTypeExceptionRaisedIfBadTypeSupplied() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "testprocessor", [
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
}