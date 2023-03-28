<?php


namespace Kinintel\Services\Dataset;


use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTask;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;

class DatasetEvaluatorLongRunningTask extends LongRunningTask {

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * @var DatasetInstanceSummary
     */
    private $datasetInstanceSummary;

    /**
     * @var integer
     */
    private $offset;

    /**
     * @var integer
     */
    private $limit;


    /**
     * DatasetLongRunningTask constructor.
     *
     * @param $datasetInstanceSummary
     * @param int $offset
     * @param int $limit
     */
    public function __construct($datasetService, $datasetInstanceSummary, $offset = 0, $limit = 25) {
        $this->datasetService = $datasetService;
        $this->datasetInstanceSummary = $datasetInstanceSummary;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * Implement pass through run method to call the method on the dataset service
     *
     * @return \Kinintel\Objects\Dataset\Dataset
     */
    public function run() {

        $dataSet = $this->datasetService->getEvaluatedDataSetForDataSetInstance($this->datasetInstanceSummary, [], [],
            $this->offset, $this->limit);

        /**
         * @var ObjectBinder $objectBinder
         */
        $objectBinder = Container::instance()->get(ObjectBinder::class);
        return $objectBinder->bindToArray($dataSet);

        // Return array tabularised version to prevent issues with double evaluation
        return $dataSet;


    }
}