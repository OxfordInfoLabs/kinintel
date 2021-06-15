<?php


namespace Kinintel\Traits\Controller\Account;

use Kinintel\Objects\Datasource\DatasourceInstanceSummary;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\EvaluatedDataSource;

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
     * @http POST /parameters/$key
     *
     * @param $key
     * @param $transformationInstances
     */
    public function getEvaluatedParameters($key, $transformationInstances) {
        return $this->datasourceService->getEvaluatedParameters($key, $transformationInstances);
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
        return $this->datasourceService->getEvaluatedDataSource($evaluatedDataSource->getKey(), $evaluatedDataSource->getParameterValues(), $evaluatedDataSource->getTransformationInstances());
    }


}