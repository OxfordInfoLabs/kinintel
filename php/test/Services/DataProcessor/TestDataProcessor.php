<?php


namespace Kinintel\Test\Services\DataProcessor;


use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessor;

class TestDataProcessor implements DataProcessor {

    public $processedConfig = null;


    public function getConfigClass() {
        return TestDataProcessorConfig::class;
    }

    /**
     * @param DataProcessorInstance $instance
     * @return void
     */
    public function process($instance) {
        $this->processedConfig = $instance->returnConfig();
    }

    public function onInstanceDelete($instance) {

    }

}