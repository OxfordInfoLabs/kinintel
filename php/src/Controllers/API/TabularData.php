<?php


namespace Kinintel\Controllers\API;

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
     * @return mixed
     */
    public function insert($importKey, $rows) {
        $datasourceUpdate = new DatasourceUpdate($rows);
        $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return ["added" => sizeof($rows)];
    }


    /**
     * Update a set of rows to the data source indentified by the passed import key
     *
     * @http PUT /$importKey
     *
     * @param string $importKey
     * @param mixed[] $rows
     * @return mixed
     */
    public function update($importKey, $rows) {
        $datasourceUpdate = new DatasourceUpdate([], $rows);
        $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return ["updated" => sizeof($rows)];
    }


    /**
     * Replace a set of rows to the data source identified by the passed import key
     *
     * @http PATCH /$importKey
     *
     * @param string $importKey
     * @param mixed[] $rows
     * @return mixed
     */
    public function replace($importKey, $rows) {
        $datasourceUpdate = new DatasourceUpdate([], [], [], $rows);
        $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return ["replaced" => sizeof($rows)];
    }


    /**
     * Replace a set of rows to the data source identified by the passed import key
     *
     * @http DELETE /$importKey
     *
     * @param string $importKey
     * @param mixed[] $deletePKs
     * @return mixed
     */
    public function delete($importKey, $deletePKs) {
        $datasourceUpdate = new DatasourceUpdate([], [], $deletePKs);
        $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return ["deleted" => sizeof($deletePKs)];
    }


}