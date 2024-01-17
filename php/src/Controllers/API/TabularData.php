<?php


namespace Kinintel\Controllers\API;

use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;

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


    /**
     * Delete a set of rows according to the passed filters which will be applied as
     * a single delete criteria.
     *
     * @http DELETE /filtered/$importKey
     *
     * @param string $importKey
     * @param mixed[] $filters
     *
     * @return void
     */
    public function filteredDelete($importKey, $filters = []) {

        // Grab the instance
        $instance = $this->datasourceService->getDataSourceInstanceByImportKey($importKey);
        $dataSource = $instance->returnDataSource();
        $columns = $dataSource->getConfig()->getColumns();
        $columnNames = ObjectArrayUtils::getMemberValueArrayForObjects("name", $columns);

        /**
         * Loop through supplied filters and map to full filter array
         */
        $mappedFilters = [];
        foreach ($filters as $columnName => $value) {

            if (!in_array($columnName, $columnNames)) {
                throw new DatasourceUpdateException("Column '$columnName' does not exist on the datasource with key '$importKey' being updated");
            }

            if (is_array($value) && isset($value["value"])) {
                $matchType = $value["matchType"] ?? Filter::FILTER_TYPE_EQUALS;
                $value = $value["value"];
            } else {
                $matchType = is_array($value) ? Filter::FILTER_TYPE_IN : Filter::FILTER_TYPE_EQUALS;
            }
            $mappedFilters[] = new Filter("[[" . $columnName . "]]", $value, $matchType);
        }


        $this->datasourceService->filteredDeleteFromDatasourceInstanceByImportKey($importKey, new FilterJunction($mappedFilters));
    }


}