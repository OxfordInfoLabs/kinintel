<?php


namespace Kinintel\Controllers\API;

use Kiniauth\Objects\Security\APIKey;
use Kiniauth\Services\Security\SecurityService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;

class TabularData {

    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     * Import constructor.
     *
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
    }


    /**
     * Insert a set of rows to the data source indentified by the passed import key
     *
     * @http POST /$importKey
     *
     * @param string $importKey
     * @param mixed[] $rows
     */
    public function insert($importKey, $rows) {
        $datasourceUpdate = new DatasourceUpdate($rows);
        $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
    }


}