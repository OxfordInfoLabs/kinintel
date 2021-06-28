<?php

namespace Kinintel\Objects\Dataset;

use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Traits\Account\AccountProject;


/**
 * @table ki_dataset_instance
 * @generate
 */
class DatasetInstance extends DatasetInstanceSummary {

    // use account project trait
    use AccountProject;

    /**
     * @var ObjectTag[]
     * @oneToMany
     * @childJoinColumns object_id, object_type=KiDatasetInstance
     */
    protected $tags;

    /**
     * DatasetInstance constructor - used to convert summaries
     *
     * @param DatasetInstanceSummary $datasetInstanceSummary
     * @param integer $accountId
     * @param integer $projectNumber
     */
    public function __construct($datasetInstanceSummary = null, $accountId = null, $projectKey = null) {
        if ($datasetInstanceSummary instanceof DatasetInstanceSummary)
            parent::__construct($datasetInstanceSummary->getTitle(), $datasetInstanceSummary->getDatasourceInstanceKey(), $datasetInstanceSummary->getTransformationInstances(),
                $datasetInstanceSummary->getParameters(),
                $datasetInstanceSummary->getParameterValues(), $datasetInstanceSummary->getId());
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
    }

    /**
     * @return ObjectTag[]
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * @param ObjectTag[] $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }


    /**
     * @return DatasetInstanceSummary
     */
    public function returnSummary() {
        return new DatasetInstanceSummary($this->title, $this->datasourceInstanceKey, $this->transformationInstances, $this->parameters, $this->parameterValues, $this->id);
    }


}