<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class DatasetService {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var MetaDataService
     */
    private $metaDataService;


    /**
     * DatasetService constructor.
     *
     * @param DatasourceService $datasourceService
     * @param MetaDataService $metaDataService
     */
    public function __construct($datasourceService, $metaDataService) {
        $this->datasourceService = $datasourceService;
        $this->metaDataService = $metaDataService;
    }


    /**
     * Get a data set instance by id
     *
     * @param $id
     * @return DatasetInstanceSummary
     */
    public function getDataSetInstance($id) {
        return DatasetInstance::fetch($id)->returnSummary();
    }


    /**
     * Save a data set instance
     *
     * @param DatasetInstanceSummary $dataSetInstanceSummary
     */
    public function saveDataSetInstance($dataSetInstanceSummary, $accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {
        $dataSetInstance = new DatasetInstance($dataSetInstanceSummary, $accountId, $projectKey);

        // Process tags
        if (sizeof($dataSetInstanceSummary->getTags())) {
            $tags = $this->metaDataService->getObjectTagsFromSummaries($dataSetInstanceSummary->getTags(), $accountId, $projectKey);
            $dataSetInstance->setTags($tags);
        }


        $dataSetInstance->save();
        return $dataSetInstance->getId();
    }


    /**
     * Remove the data set instance by id
     *
     * @param $id
     */
    public function removeDataSetInstance($id) {
        $dataSetInstance = DatasetInstance::fetch($id);
        $dataSetInstance->remove();
    }


    /**
     * Wrapper to below function for standard read only use where a data set is being
     * queried
     *
     * @param $dataSetInstanceId
     * @param TransformationInstance[] $additionalTransformations
     */
    public function getEvaluatedDataSetForDataSetInstanceById($dataSetInstanceId, $additionalTransformations = []) {
        $dataSetInstance = $this->getDataSetInstance($dataSetInstanceId);
        return $this->getEvaluatedDataSetForDataSetInstance($dataSetInstance, $additionalTransformations);
    }


    /**
     * Wrapper to below function which also calls the materialise function to just return
     * the dataset.  This is the normal function called to produce charts / tables etc for end
     * use.
     *
     * @param DatasetInstanceSummary $dataSetInstance
     * @param TransformationInstance[] $additionalTransformations
     *
     */
    public function getEvaluatedDataSetForDataSetInstance($dataSetInstance, $additionalTransformations = []) {
        $evaluatedDataSource = $this->getEvaluatedDataSourceForDataSetInstance($dataSetInstance, $additionalTransformations);
        return $evaluatedDataSource->materialise();
    }


    /**
     * Get the evaluated data source for a data set instance.  This is the resultant
     * data source returned after all transformations have been applied
     *
     * @param DatasetInstanceSummary $dataSetInstance
     * @param TransformationInstance[] $additionalTransformations
     *
     * @return BaseDatasource
     */
    public function getEvaluatedDataSourceForDataSetInstance($dataSetInstance, $additionalTransformations = []) {


        // Grab the datasource for this data set instance by key
        $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($dataSetInstance->getDatasourceInstanceKey());

        // Grab the data source for this instance
        $datasource = $datasourceInstance->returnDataSource();

        // If we have transformation instances, apply these in sequence
        if ($dataSetInstance->getTransformationInstances()) {
            foreach ($dataSetInstance->getTransformationInstances() as $transformationInstance) {
                $transformation = $transformationInstance->returnTransformation();
                $datasource = $datasource->applyTransformation($transformation);
            }
        }

        if ($additionalTransformations ?? []) {
            foreach ($additionalTransformations as $transformationInstance) {
                $transformation = $transformationInstance->returnTransformation();
                $datasource = $datasource->applyTransformation($transformation);
            }
        }


        // Return the evaluated data source
        return $datasource;

    }


}