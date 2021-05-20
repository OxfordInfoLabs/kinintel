<?php


namespace Kinintel\Traits\Controller\Account;

use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\ValueObjects\Dataset\DatasetInstanceEvaluationDescriptor;

/**
 * Dataset service, acts on both in process and saved datasets
 *
 * Trait Dataset
 * @package Kinintel\Traits\Controller\Account
 */
trait Dataset {

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * Dataset constructor.
     *
     * @param DatasetService $datasetService
     */
    public function __construct($datasetService) {
        $this->datasetService = $datasetService;
    }


    /**
     * Get a dataset instance summary by id
     *
     * @http GET /$id
     *
     * @param $id
     * @return DatasetInstanceSummary
     */
    public function getDatasetInstance($id) {
        return $this->datasetService->getDataSetInstance($id);
    }


    /**
     * Filter dataset instances, optionally by title, project key, tags and limited by offset and limit.
     *
     * @http GET /
     *
     * @param string $filterString
     * @param string $projectKey
     * @param string $tags
     * @param int $offset
     * @param int $limit
     *
     * @return \Kinintel\Objects\Dataset\DatasetInstanceSearchResult[]
     */
    public function filterDatasetInstances($filterString = "", $projectKey = null, $tags = "", $offset = 0, $limit = 10) {
        $tags = $tags ? explode(",", $tags) : [];
        return $this->datasetService->filterDataSetInstances($filterString, $tags, $projectKey, $offset, $limit);
    }


    /**
     * Save a data set instance object
     *
     * @http POST
     *
     * @param DatasetInstanceSummary $dataSetInstanceSummary
     * @param string $projectKey
     */
    public function saveDatasetInstance($dataSetInstanceSummary, $projectKey = null) {
        $this->datasetService->saveDataSetInstance($dataSetInstanceSummary, $projectKey);
    }


    /**
     * Remove a dataset instance by id
     *
     * @param $id
     */
    public function removeDatasetInstance($id) {
        $this->datasetService->removeDataSetInstance($id);
    }


    /**
     * Evaluate a dataset instance (used within the GUI)
     *
     * @http POST /evaluate
     *
     * @param DatasetInstanceEvaluationDescriptor $datasetInstanceEvaluationDescriptor
     */
    public function evaluateDatasetInstance($datasetInstanceEvaluationDescriptor) {
        return $this->datasetService->getEvaluatedDataSetForDataSetInstance($datasetInstanceEvaluationDescriptor->getDatasetInstanceSummary(), $datasetInstanceEvaluationDescriptor->getAdditionalTransformations());
    }

}