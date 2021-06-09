<?php


namespace Kinintel\Test\Services\DataProcessor;


use Kinintel\Services\DataProcessor\DataProcessor;

class TestDataProcessor implements DataProcessor {

    public $processedConfig = null;


    public function getConfigClass() {
        return TestDataProcessorConfig::class;
    }

    public function process($config = null) {
        $this->processedConfig = $config;
    }
}