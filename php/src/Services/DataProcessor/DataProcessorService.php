<?php


namespace Kinintel\Services\DataProcessor;


class DataProcessorService {

    /**
     * @var DataProcessorDAO
     */
    private $dataProcessorDAO;

    /**
     * DataProcessorService constructor.
     *
     * @param DataProcessorDAO $dataProcessorDAO
     */
    public function __construct($dataProcessorDAO) {
        $this->dataProcessorDAO = $dataProcessorDAO;
    }

    /**
     * Process a data processor instance using related processor
     *
     * @param string $instanceKey
     */
    public function processDataProcessorInstance($instanceKey) {
    }


}