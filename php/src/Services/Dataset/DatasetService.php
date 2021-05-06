<?php

namespace Kinintel\Services\Dataset;

use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class DatasetService {

    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     * DatasetService constructor.
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;

    }


    /**
     * Get a data set instance by id
     *
     * @param $id
     * @return DatasetInstance
     */
    public function getDataSetInstance($id) {
        return DatasetInstance::fetch($id);
    }


    /**
     * Save a data set instance
     *
     * @param DatasetInstance $dataSetInstance
     */
    public function saveDataSetInstance($dataSetInstance) {
        $dataSetInstance->save();
        return $dataSetInstance->getId();
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
     * @param $dataSetInstance
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
     * @param DatasetInstance $dataSetInstance
     * @param TransformationInstance[] $additionalTransformations
     *
     * @return Datasource
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