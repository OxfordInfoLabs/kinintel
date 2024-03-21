<?php

namespace Kinintel\Test\Objects\DataProcessor;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\DataProcessor\DataProcessorInstanceInterceptor;
use Kinintel\Services\DataProcessor\DataProcessor;

include_once "autoloader.php";

class DataProcessorInstanceInterceptorTest extends \PHPUnit\Framework\TestCase {

    public function testPreSaveInterceptorCallsOnInstanceSaveMethodOnDataProcessorWithInstance() {

        $instance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $dataProcessor = MockObjectProvider::instance()->getMockInstance(DataProcessor::class);
        $instance->returnValue("returnProcessor", $dataProcessor);

        $interceptor = new DataProcessorInstanceInterceptor();
        $interceptor->preSave($instance);

        $this->assertTrue($dataProcessor->methodWasCalled("onInstanceSave", [$instance]));

    }


    public function testPreDeleteInterceptorCallsOnInstanceDeleteMethodOnDataProcessorWithInstance() {

        $instance = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $dataProcessor = MockObjectProvider::instance()->getMockInstance(DataProcessor::class);
        $instance->returnValue("returnProcessor", $dataProcessor);

        $interceptor = new DataProcessorInstanceInterceptor();
        $interceptor->preDelete($instance);

        $this->assertTrue($dataProcessor->methodWasCalled("onInstanceDelete", [$instance]));

    }

}