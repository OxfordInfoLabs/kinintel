<?php

namespace Kinintel\Test\Services\DataProcessor\Multi;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\DataProcessor\Multi\MultiDataProcessor;
use Kinintel\ValueObjects\DataProcessor\Configuration\Multi\MultiDataProcessorConfiguration;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class MultiDataProcessorTest extends TestCase {

    /**
     * @var MockObject
     */
    private $dataProcessorService;

    public function setUp(): void {
        $this->dataProcessorService = MockObjectProvider::instance()->getMockInstance(DataProcessorService::class);
    }

    public function testCanExecuteMultipleDataProcessorsByKeys() {

        $dataProcessor = new MultiDataProcessor($this->dataProcessorService);
        $config = new MultiDataProcessorConfiguration(["testKey1", "testKey2"]);

        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $config, []);

        $dataProcessor->process($processorInstance);

        $this->assertTrue($this->dataProcessorService->methodWasCalled("processDataProcessorInstance", ["testKey1"]));
        $this->assertTrue($this->dataProcessorService->methodWasCalled("processDataProcessorInstance", ["testKey2"]));

    }

    public function testCanExecuteMultipleDataProcessorsByInstances() {

        $dataProcessor = new MultiDataProcessor($this->dataProcessorService);
        $instanceOne = new DataProcessorInstance("key1", "title1", "type1");
        $instanceTwo = new DataProcessorInstance("key2", "title2", "type2");

        $config = new MultiDataProcessorConfiguration([], [$instanceOne, $instanceTwo]);

        $processorInstance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $processorInstance->returnValue("returnConfig", $config, []);

        $dataProcessor->process($processorInstance);

        $this->assertTrue($this->dataProcessorService->methodWasCalled("processDataProcessorInstanceObject", [$instanceOne]));
        $this->assertTrue($this->dataProcessorService->methodWasCalled("processDataProcessorInstanceObject", [$instanceTwo]));

    }

}