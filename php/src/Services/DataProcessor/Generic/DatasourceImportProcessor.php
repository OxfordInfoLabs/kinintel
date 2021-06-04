<?php


namespace Kinintel\Services\DataProcessor\Generic;


use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImportProcessorConfiguration;

class DatasourceImportProcessor implements DataProcessor {

    /**
     * Return the configuration class for the import processor
     *
     * @return string
     */
    public function getConfigClass() {
        return DatasourceImportProcessorConfiguration::class;
    }

    /**
     * Process a datasource import
     *
     * @param  $config
     */
    public function process($config = null) {

    }
}