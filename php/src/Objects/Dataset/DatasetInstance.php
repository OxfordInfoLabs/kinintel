<?php

namespace Kinintel\Objects\Dataset;

use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\ObjectCategory;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Services\Security\SecurityService;
use Kiniauth\Traits\Account\AccountProject;
use Kiniauth\Traits\Security\Sharable;
use Kinikit\Core\DependencyInjection\Container;


/**
 * @table ki_dataset_instance
 * @generate
 * @interceptor \Kinintel\Objects\Dataset\DatasetInstanceInterceptor
 */
class DatasetInstance extends DatasetInstanceSummary {

    // use account project trait
    use AccountProject;
    use Sharable;

    /**
     * @var ObjectTag[]
     * @oneToMany
     * @childJoinColumns object_id, object_type=KiDatasetInstance
     */
    protected $tags;


    /**
     * @var ObjectCategory[]
     * @oneToMany
     * @childJoinColumns object_id, object_type=KiDatasetInstance
     */
    protected $categories = [];



    /**
     * DatasetInstance constructor - used to convert summaries
     *
     * @param DatasetInstanceSummary $datasetInstanceSummary
     * @param integer $accountId
     * @param integer $projectNumber
     */
    public function __construct($datasetInstanceSummary = null, $accountId = null, $projectKey = null) {
        if ($datasetInstanceSummary instanceof DatasetInstanceSummary)
            parent::__construct($datasetInstanceSummary->getTitle(),
                $datasetInstanceSummary->getDatasourceInstanceKey(),
                $datasetInstanceSummary->getDatasetInstanceId(),
                $datasetInstanceSummary->getTransformationInstances(),
                $datasetInstanceSummary->getParameters(),
                $datasetInstanceSummary->getParameterValues(),
                $datasetInstanceSummary->getSummary(),
                $datasetInstanceSummary->getDescription(),
                $datasetInstanceSummary->getCategories(),
                $datasetInstanceSummary->getId(),
                $datasetInstanceSummary->sourceDataset,
                $datasetInstanceSummary->getManagementKey());
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
     * @return ObjectCategory[]
     */
    public function getCategories() {
        return $this->categories;
    }

    /**
     * @param ObjectCategory[] $categories
     */
    public function setCategories($categories) {
        $this->categories = $categories;
    }


    public function getSharableTypeLabel(): string {
        return "Data set";
    }

    public function getSharableTitle(): string {
        return $this->title;
    }


    /**
     * @return DatasetInstanceSummary
     */
    public function returnSummary($enforceReadOnly = true) {

        /**
         * @var SecurityService $securityService
         */
        $securityService = Container::instance()->get(SecurityService::class);
        $readOnly = $enforceReadOnly && !$securityService->isSuperUserLoggedIn() && $this->accountId == null;

        if ($readOnly) {
            $parameterValues = [];
            foreach ($this->parameterValues as $parameterKey => $parameterValue) {
                $parameterValues[$parameterKey] = null;
            }
        } else {
            $parameterValues = $this->parameterValues;
        }


        // Map categories to summary objects
        $newCategories = [];
        foreach ($this->categories as $category) {
            if ($category instanceof ObjectCategory) {
                if ($category->getCategory())
                    $newCategories[] = new CategorySummary($category->getCategory()->getCategory(), $category->getCategory()->getDescription(), $category->getCategory()->getKey());
            } else if ($category instanceof CategorySummary) {
                $newCategories[] = $category;
            }
        }


        return new DatasetInstanceSummary($this->title, $readOnly ? null : $this->datasourceInstanceKey,
            $readOnly ? $this->id : $this->datasetInstanceId,
            $readOnly ? [] : $this->transformationInstances,
            $readOnly ? [] : $this->parameters,
            $parameterValues,
            $this->summary,
            $this->description,
            $newCategories,
            $readOnly ? null : $this->id,
            $this->sourceDataset,
            $this->managementKey);
    }


}
