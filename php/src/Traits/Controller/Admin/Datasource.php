<?php


namespace Kinintel\Traits\Controller\Admin;

use Kinintel\Objects\Datasource\DatasourceInstanceSummary;
use Kinintel\Services\Datasource\CustomDatasourceService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\EvaluatedDataSource;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

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
     * @var CustomDatasourceService
     */
    private $customDatasourceService;

    /**
     * Datasource constructor.
     * @param DatasourceService $datasourceService
     * @param CustomDatasourceService $customDatasourceService
     */
    public function __construct($datasourceService, $customDatasourceService) {
        $this->datasourceService = $datasourceService;
        $this->customDatasourceService = $customDatasourceService;
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
        return $this->datasourceService->getEvaluatedDataSource($evaluatedDataSource->getKey(),
            $evaluatedDataSource->getParameterValues(), $evaluatedDataSource->getTransformationInstances(),
            $evaluatedDataSource->getOffset() ?? 0, $evaluatedDataSource->getLimit() ?? 25);
    }

    /**
     * Create a new custom datasource instance.  Return the datasource key
     *
     * @http POST /custom
     *
     * @param DatasourceUpdateWithStructure $datasourceUpdate
     * @param string $projectKey
     *
     * @return string
     */
    public function createCustomDatasourceInstance($datasourceUpdate, $projectKey = null) {
        return $this->customDatasourceService->createCustomDatasourceInstance($datasourceUpdate, $projectKey, null);
    }


    /**
     * Update a custom datasource instance with the supplied data and optionally structure
     *
     *
     * @http PUT /custom/$datasourceInstanceKey
     *
     * @param string $datasourceInstanceKey
     * @param DatasourceUpdateWithStructure $datasourceUpdate
     */
    public function updateCustomDatasourceInstance($datasourceInstanceKey, $datasourceUpdate) {
        $this->datasourceService->updateDatasourceInstanceByKey($datasourceInstanceKey, $datasourceUpdate);
    }

}
