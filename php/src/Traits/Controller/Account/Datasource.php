<?php


namespace Kinintel\Traits\Controller\Account;

use Kinintel\Objects\Datasource\DatasourceInstanceSummary;
use Kinintel\Services\Datasource\DatasourceService;

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
     * @param int $filterString
     * @param int $limit
     * @param int $offset
     */
    public function filterDatasourceInstances($filterString = "", $limit = 10, $offset = 0) {
        return $this->datasourceService->filterDatasourceInstances($filterString, $limit, $offset);
    }


}