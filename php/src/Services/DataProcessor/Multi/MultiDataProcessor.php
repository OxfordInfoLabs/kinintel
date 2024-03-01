<?php

namespace Kinintel\Services\DataProcessor\Multi;

use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\ValueObjects\DataProcessor\Configuration\Multi\MultiDataProcessorConfiguration;

class MultiDataProcessor implements DataProcessor {


    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;

    /**
     * Constructor
     *
     * @param DataProcessorService $dataProcessorService
     */
    public function __construct($dataProcessorService) {
        $this->dataProcessorService = $dataProcessorService;
    }


    /**
     * Return the configuration class for the import processor
     *
     * @return string
     */
    public function getConfigClass() {
        return MultiDataProcessorConfiguration::class;
    }

    /**
     * Process a datasource import
     *
     * @param DataProcessorInstance $instance
     */
    public function process($instance) {

        /**
         * @var MultiDataProcessorConfiguration $config
         */
        $config = $instance->returnConfig();

        // Execute each of the data processors
        if ($config->getDataProcessorKeys()) {
            foreach ($config->getDataProcessorKeys() as $dataProcessorKey) {
                $this->dataProcessorService->processDataProcessorInstance($dataProcessorKey);
            }
        } else {
            foreach ($config->getDataProcessors() as $dataProcessor) {
                $this->dataProcessorService->processDataProcessorInstanceObject($dataProcessor);
            }
        }
    }

    public function onInstanceDelete($instance) {

    }

}