<?php


namespace Kinintel\Traits\Controller\Account;

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
     * Filter datasources
     *
     * @http GET /
     *
     * @param int $filterString
     * @param int $limit
     * @param int $offset
     */
    public function filterDatasources($filterString = 0, $limit = 10, $offset = 0) {
        return $this->datasourceService->filterDatasources($filterString, $limit, $offset);
    }


}