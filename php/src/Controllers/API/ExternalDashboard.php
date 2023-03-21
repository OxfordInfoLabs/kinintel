<?php


namespace Kinintel\Controllers\API;

use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetEvaluatorLongRunningTask;

/**
 * External dashboard API methods
 *
 * Class ExternalDashboard
 * @package Kinintel\Controllers\API
 */
class ExternalDashboard {

    /**
     * Get an external dashboard by id
     *
     * @param $id
     */
    public function getExternalDashboard($id) {

    }


    /**
     * Evaluate a dataset and return a dataset
     *
     * @http POST /evaluate
     *
     * @unsanitise $datasetInstanceSummary
     *
     * @param DatasetInstanceSummary $datasetInstanceSummary
     * @param integer $offset
     * @param integer $limit
     * @param string $trackingKey
     * @param string $projectKey
     *
     * @return \Kinintel\Objects\Dataset\Dataset
     */
    public function evaluateDataset($datasetInstanceSummary, $offset = 0, $limit = 25, $trackingKey = null, $projectKey = null) {

    }



}