<?php


namespace Kinintel\Traits\Controller\Account;

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