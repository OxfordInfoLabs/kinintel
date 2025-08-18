<?php


namespace Kinintel\Controllers\API;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Response\JSONResponse;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Exception\FieldNotFoundException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Util\FilterQueryParser;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\Sort;
use Kinintel\ValueObjects\Transformation\TransformationInstance;
use League\Uri\Exception;

class TabularData {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var FilterQueryParser
     */
    private $filterQueryParser;

    /**
     * Import constructor.
     *
     * @param DatasourceService $datasourceService
     * @param FilterQueryParser $filterQueryParser
     */
    public function __construct($datasourceService, $filterQueryParser) {
        $this->datasourceService = $datasourceService;
        $this->filterQueryParser = $filterQueryParser;
    }

    /**
     * @return void
     */
    public function handleRequest() {
        throw new Exception("Invalid endpoint called");
    }


    /**
     * @http GET /$importKey
     *
     * @param string $importKey
     * @param Request $request
     *
     * @return Dataset
     */
    public function list($importKey, $request) {

        $datasource = $this->datasourceService->getDataSourceInstanceByImportKey($importKey);

        $params = $request->getParameters();

        // Grab offset and limit if they have been passed
        $offset = $params["offset"] ?? 0;
        $limit = $params["limit"] ?? 100;

        unset($params["offset"]);
        unset($params["limit"]);

        // Grab columns
        $columns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $datasource->returnDataSource()->getConfig()->getColumns());

        // Filters
        $transformationInstances = [];
        $filters = [];

        // Handle legacy filter format (TO BE DEPRECATED)
        foreach ($params as $key => $value) {

            if (str_starts_with($key, "filter_")) {
                $key = substr($key, 7);

                if (!isset($columns[$key]))
                    throw new FieldNotFoundException($key, "column", "filtering");

                $explodedValue = explode("|", $value);
                $matchValue = array_shift($explodedValue);
                $filters[] = new Filter("[[" . $key . "]]", $matchValue, isset($explodedValue[0]) ? FilterType::fromString($explodedValue[0]) : FilterType::eq);
            }
        }

        // Handle new core filter format
        $filters = array_merge($filters, Filter::createFiltersFromFieldTypeIndexedArray($params));

        if (sizeof($filters))
            $transformationInstances[] = new TransformationInstance("filter", new FilterTransformation($filters));


        // Handle advanced queries
        if ($params["query"] ?? null) {
            $filterJunction = $this->filterQueryParser->convertQueryToFilterJunction($params["query"]);
            $transformationInstances[] = new TransformationInstance("filter", new FilterTransformation($filterJunction->getFilters(),
                $filterJunction->getFilterJunctions(), $filterJunction->getLogic()));
        }

        if ($params["sort"] ?? null) {
            $splitSort = explode("|", $params["sort"]);
            $sorts = [];
            foreach ($splitSort as $index => $sortItem) {
                if ($index % 2 == 0) {
                    if (!isset($columns[$sortItem]))
                        throw new FieldNotFoundException($sortItem, "column", "sorting");
                    $sorts[] = new Sort($sortItem, $splitSort[$index + 1] ?? Sort::DIRECTION_ASC);
                }
            }
            if (sizeof($sorts))
                $transformationInstances[] = new TransformationInstance("multisort", new MultiSortTransformation($sorts));

        }


        $dataset = $this->datasourceService->getEvaluatedDataSource($datasource, [], $transformationInstances, $offset, $limit);
        return $dataset?->getAllData();
    }


    /**
     * Insert a set of rows to the data source identified by the passed import key
     *
     * @http POST /$importKey
     *
     * @param string $importKey
     * @param mixed[] $rows
     * @return mixed
     */
    public function insert($importKey, $rows) {
        $datasourceUpdate = new DatasourceUpdate($rows);
        $result = $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return new JSONResponse($result, $result->getRejected() > 0 ? 422 : 200);
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
        $result = $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return new JSONResponse($result, $result->getRejected() > 0 ? 422 : 200);

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
        $result = $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return new JSONResponse($result, $result->getRejected() > 0 ? 422 : 200);
    }


    /**
     * Replace a set of rows to the data source identified by the passed import key
     *
     * @http DELETE /$importKey
     *
     * @param string $importKey
     * @param mixed[] $pkValues
     * @return mixed
     */
    public function delete($importKey, $pkValues) {
        $datasourceUpdate = new DatasourceUpdate([], [], $pkValues);
        $result = $this->datasourceService->updateDatasourceInstanceByImportKey($importKey, $datasourceUpdate);
        return new JSONResponse($result, $result->getRejected() > 0 ? 422 : 200);
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
        foreach ($filters as $filter) {

            $columnName = $filter["column"] ?? null;

            if (!in_array($columnName, $columnNames)) {
                throw new DatasourceUpdateException("Column '$columnName' does not exist on the datasource with key '$importKey' being updated");
            }

            $value = $filter["value"] ?? null;

            $matchType = $filter["matchType"] ?? (is_array($value) ? FilterType::in : FilterType::eq);


            $mappedFilters[] = new Filter("[[" . $columnName . "]]", $value, $matchType);
        }


        $this->datasourceService->filteredDeleteFromDatasourceInstanceByImportKey($importKey, new FilterJunction($mappedFilters));

        return ["status" => "success"];
    }


}