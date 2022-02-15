<?php


namespace Kinintel\Traits\Controller\Account;

use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstanceSummary;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\EvaluatedDataSource;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

/**
 * Datasource trait for account level access to datasources
 *
 * Class Datasource
 * @package Kinintel\Traits\Controller\Account
 */
trait Datasource {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * Datasource constructor.
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
    }


    /**
     * Get a datasource by key
     *
     * @http GET /$key
     *
     * @param $key
     * @return DatasourceInstanceSummary
     */
    public function getDatasourceInstance($key) {
        return $this->datasourceService->getDataSourceInstanceByKey($key);
    }


    /**
     * Filter datasource instances
     *
     * @http GET /
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     */
    public function filterDatasourceInstances($filterString = "", $limit = 10, $offset = 0) {
        return $this->datasourceService->filterDatasourceInstances($filterString, $limit, $offset);
    }


    /**
     * Get the evaluated parameters for the supplied instance by key.
     * The array of transformation instances can be supplied as payload.
     *
     * @http GET /parameters/$key
     *
     * @param string $key
     */
    public function getEvaluatedParameters($key) {
        return $this->datasourceService->getEvaluatedParameters($key);
    }

    /**
     * Evaluate a datasource and return a dataset
     *
     * @http POST /evaluate
     *
     * @param EvaluatedDataSource $evaluatedDataSource
     * @return \Kinintel\Objects\Dataset\Dataset
     */
    public function evaluateDatasource($evaluatedDataSource) {
        return $this->datasourceService->getEvaluatedDataSource($evaluatedDataSource->getKey(), $evaluatedDataSource->getParameterValues(), $evaluatedDataSource->getTransformationInstances(),
            $evaluatedDataSource->getOffset() ?? 0, $evaluatedDataSource->getLimit() ?? 25);
    }


    /**
     * Update a datasource instance with the supplied data and optionally structure
     *
     *
     * @http PUT /$datasourceInstanceKey
     *
     * @param string $datasourceInstanceKey
     * @param DatasourceUpdateWithStructure $data
     */
    public function updateDatasourceInstance($datasourceInstanceKey, $datasourceUpdate) {
        $this->datasourceService->updateDatasourceInstance($datasourceInstanceKey, $datasourceUpdate);
    }


}