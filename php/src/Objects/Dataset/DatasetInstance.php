<?php

namespace Kinintel\Objects\Dataset;

use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\DependencyInjection\Container;


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
     * @var DatasetInstanceSnapshotProfile[]
     * @oneToMany
     * @childJoinColumns dataset_instance_id
     */
    protected $snapshotProfiles;


    /**
     * DatasetInstance constructor - used to convert summaries
     *
     * @param DatasetInstanceSummary $datasetInstanceSummary
     * @param integer $accountId
     * @param integer $projectNumber
     */
    public function __construct($datasetInstanceSummary = null, $accountId = null, $projectKey = null) {
        if ($datasetInstanceSummary instanceof DatasetInstanceSummary)
            parent::__construct($datasetInstanceSummary->getTitle(), $datasetInstanceSummary->getDatasourceInstanceKey(), $datasetInstanceSummary->getDatasetInstanceId(), $datasetInstanceSummary->getTransformationInstances(),
                $datasetInstanceSummary->getParameters(),
                $datasetInstanceSummary->getParameterValues(),
                $datasetInstanceSummary->getId());
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

        /**
         * @var SecurityService $securityService
         */
        $securityService = Container::instance()->get(SecurityService::class);
        $readOnly = !$securityService->isSuperUserLoggedIn() && $this->accountId == null;

        return new DatasetInstanceSummary($this->title, $readOnly ? null : $this->datasourceInstanceKey,
            $readOnly ? $this->id : $this->datasetInstanceId,
            $readOnly ? [] : $this->transformationInstances,
            $readOnly ? [] : $this->parameters,
            $readOnly ? [] : $this->parameterValues,
            $readOnly ? null : $this->id, $readOnly);
    }


}