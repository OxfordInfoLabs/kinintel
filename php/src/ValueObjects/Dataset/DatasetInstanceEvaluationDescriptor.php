<?php


namespace Kinintel\ValueObjects\Dataset;

use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * Single payload wrapper for a dataset instance being evaluated to capture both the
 * dataset instance summary object and any additional transformations
 *
 * Class DatasetInstanceEvaluationDescriptor
 * @package Kinintel\ValueObjects\Dataset
 */
class DatasetInstanceEvaluationDescriptor {

    /**
     * @var DatasetInstanceSummary
     */
    private $datasetInstanceSummary;

    /**
     * @var TransformationInstance[]
     */
    private $additionalTransformations = [];

    /**
     * @return DatasetInstanceSummary
     */
    public function getDatasetInstanceSummary() {
        return $this->datasetInstanceSummary;
    }

    /**
     * @param DatasetInstanceSummary $datasetInstanceSummary
     */
    public function setDatasetInstanceSummary($datasetInstanceSummary) {
        $this->datasetInstanceSummary = $datasetInstanceSummary;
    }

    /**
     * @return TransformationInstance[]
     */
    public function getAdditionalTransformations() {
        return $this->additionalTransformations;
    }

    /**
     * @param TransformationInstance[] $additionalTransformations
     */
    public function setAdditionalTransformations($additionalTransformations) {
        $this->additionalTransformations = $additionalTransformations;
    }


}