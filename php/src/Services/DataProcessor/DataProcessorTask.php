<?php


namespace Kinintel\Services\DataProcessor;


use Kiniauth\Services\Workflow\Task\Task;

class DataProcessorTask implements Task {


    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;


    /**
     * DataProcessorRunner constructor
     * .
     * @param DataProcessorService $dataProcessorService
     */
    public function __construct($dataProcessorService) {
        $this->dataProcessorService = $dataProcessorService;
    }


    /**
     * Run a data processor import
     *
     * @param $configuration
     * @return bool|void
     */
    public function run($configuration) {

        if (isset($configuration["dataProcessorKey"])) {
            $this->dataProcessorService->processDataProcessorInstance($configuration["dataProcessorKey"]);
            return "Success";
        }

    }
}