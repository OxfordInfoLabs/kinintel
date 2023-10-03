<?php

namespace Kinintel\Controllers\Internal;

use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\ProcessedTabularDataSet;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class ProcessedDataset {


    /**
     * @var DatasetService
     */
    private $datasetService;


    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     *
     * @param DatasetService $datasetService
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasetService, $datasourceService) {
        $this->datasetService = $datasetService;
        $this->datasourceService = $datasourceService;
    }


    /**
     * Get a processed tabular dataset for a passed dataset instance
     *
     * @param DatasetInstanceSummary $dataSetInstance
     * @param mixed[string] $parameterValues
     * @param TransformationInstance[] $additionalTransformations
     * @param integer $offset
     * @param integer $limit
     *
     * @return ProcessedTabularDataSet
     */
    public function getProcessedTabularDatasetForDatasetInstance($dataSetInstance, $parameterValues = [], $additionalTransformations = [], $offset = null, $limit = null) {
        $matches = $this->datasetService->getEvaluatedDataSetForDataSetInstance($dataSetInstance, $parameterValues, $additionalTransformations, $offset, $limit);
        return new ProcessedTabularDataSet($matches->getColumns(), $matches->getAllData());
    }

    /**
     * @param DatasourceInstance $datasourceInstance
     * @param mixed[string] $parameterValues
     * @param TransformationInstance[] $transformations
     * @param integer $offset
     * @param integer $limit
     *
     * @return ProcessedTabularDataSet
     */
    public function getProcessedTabularDatasetForDatasourceInstance($datasourceInstance, $parameterValues = [], $transformations = [], $offset = null, $limit = null) {
        $matches = $this->datasourceService->getEvaluatedDataSource($datasourceInstance, $parameterValues, $transformations, $offset, $limit);
        return new ProcessedTabularDataSet($matches->getColumns(), $matches->getAllData());
    }

}