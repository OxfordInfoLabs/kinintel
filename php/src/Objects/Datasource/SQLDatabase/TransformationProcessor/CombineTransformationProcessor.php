<?php

namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Combine\CombineTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use function PHPUnit\Framework\returnValue;

class CombineTransformationProcessor extends SQLTransformationProcessor {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var DatasetService
     */
    private $datasetService;

    /**
     * Table index
     *
     * @var int
     */
    private $tableIndex = 0;

    /**
     * @param DatasourceService $datasourceService
     * @param DatasetService $datasetService
     */
    public function __construct(DatasourceService $datasourceService, DatasetService $datasetService) {
        $this->datasourceService = $datasourceService;
        $this->datasetService = $datasetService;
    }

    /**
     * @param CombineTransformation $transformation
     * @param Datasource $datasource
     * @param mixed $parameterValues
     * @param null $pagingTransformation
     * @return \Kinintel\Objects\Datasource\Datasource
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = [], $pagingTransformation = null) {
        // Determine which type of source we are working with.
        if ($transformation->getCombinedDataSourceInstanceKey()) {
            $this->datasourceService = $this->datasourceService ?? Container::instance()->get(DatasourceService::class);
            $combineDatasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($transformation->getCombinedDataSourceInstanceKey());
            $combineDatasource = $combineDatasourceInstance->returnDataSource();
        } else if ($transformation->getCombinedDataSetInstanceId()) {
            $this->datasetService = $this->datasetService ?? Container::instance()->get(DatasetService::class);
            $combineDataSet = $this->datasetService->getDataSetInstance($transformation->getCombinedDataSetInstanceId());
            $combineDatasource = $this->datasetService->getTransformedDatasourceForDataSetInstance($combineDataSet, $parameterValues, []);
        }

        if ($datasource->getAuthenticationCredentials() != $combineDatasource->getAuthenticationCredentials()) {

            // If we are not a default datasource, convert and reapply
            if (!($datasource instanceof DefaultDatasource)) {
                $newDatasource = new DefaultDatasource($datasource);
                return $newDatasource->applyTransformation($transformation, $parameterValues, $pagingTransformation);
            }

            if (!($combineDatasource instanceof DefaultDatasource)) {
                $combineDatasource = new DefaultDatasource($combineDatasource);
                $combineDatasource->populate($transformation->getParameterValues() ?? []);
            }

        }

        // Update the transformation with the evaluated data source.
        $transformation->setEvaluatedDataSource($combineDatasource);

        return $datasource;
    }

    /**
     * @param CombineTransformation $transformation
     * @param SQLQuery $datasource
     * @param mixed[] $parameterValues
     * @param PagingTransformation $pagingTransformation
     * @return SQLQuery|void
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        // Ensure we have an evaluated datasource before continuing
        $combineDatasource = $transformation->returnEvaluatedDataSource();

        // If we have a child query, use this to generate a new query using the various criteria.

        $childQuery = $combineDatasource->buildQuery($parameterValues);

        // Calculate the new aliases
        $mainTableAlias = "T" . ++$this->tableIndex;
        $childTableAlias = "T" . ++$this->tableIndex;
        $wrapperTableAlias = "T" . ++$this->tableIndex;
        $lhsTableAlias = "T" . ++$this->tableIndex;
        $rhsTableAlias = "T" . ++$this->tableIndex;


        $allParameters = array_merge($query->getParameters(), $childQuery->getParameters());

        $fields = $dataSource->getConfig()->getColumns();
        $fieldsAsStrings = [];
        foreach ($fields as $field) {
            $fieldsAsStrings[] = $field->getName();
        }


        Logger::log($transformation->getFieldKeyMappings());


        // Deal with any field mappings
        if ($transformation->getFieldKeyMappings()) {
            $mainSelectList = [];
            $childSelectList = [];
            $columns = [];

            foreach ($transformation->getFieldKeyMappings() as $key => $value) {
                $mainSelectList[] = $mainTableAlias . "." . $value->getName();
                $childSelectList[] = $childTableAlias . "." . $key;
                $columns[] = $value;
            }



            $mainSelect = join(",", $mainSelectList);
            $childSelect = join(",", $childSelectList);

        } else {
            $mainSelect = $mainTableAlias . ".*";
            $childSelect = $childTableAlias . ".*";
        }



        if ($transformation->getFieldKeyMappings()) {
            $dataSource->getConfig()->setColumns($columns);
        }

        // Evaluate the new SQL statement given combine type
        switch ($transformation->getCombineType()) {
            case CombineTransformation::COMBINE_TYPE_UNION:
                return new SQLQuery("*", "(SELECT $mainSelect FROM ({$query->getSQL()}) $mainTableAlias UNION SELECT $childSelect FROM ({$childQuery->getSQL()}) $childTableAlias) $wrapperTableAlias", $allParameters);

            case CombineTransformation::COMBINE_TYPE_UNION_ALL:
                return new SQLQuery("*", "(SELECT $mainSelect FROM ({$query->getSQL()}) $mainTableAlias UNION ALL SELECT $childSelect FROM ({$childQuery->getSQL()}) $childTableAlias) $wrapperTableAlias", $allParameters);

            case CombineTransformation::COMBINE_TYPE_INTERSECT:
                $fieldEqualityList = [];

                if ($transformation->getFieldKeyMappings()) {
                    foreach ($transformation->getFieldKeyMappings() as $key => $value) {
                        $fieldEqualityList[] = $lhsTableAlias . "." . $value->getName() . " = " . $rhsTableAlias . "." . $key;
                    }
                } else {
                    foreach ($fieldsAsStrings as $field) {
                        $fieldEqualityList[] = $lhsTableAlias . "." . $field . " = " . $rhsTableAlias . "." . $field;
                    }
                }

                $onClause = join(" AND ", $fieldEqualityList);
                return new SQLQuery("*", "(SELECT $mainSelect FROM ({$query->getSQL()}) $mainTableAlias) $lhsTableAlias INNER JOIN (SELECT $childSelect FROM ({$childQuery->getSQL()}) $childTableAlias) $rhsTableAlias ON $onClause", $allParameters);

            case CombineTransformation::COMBINE_TYPE_EXCEPT:
                $fieldEqualityList = [];
                $fieldNullList = [];

                if ($transformation->getFieldKeyMappings()) {
                    foreach ($transformation->getFieldKeyMappings() as $key => $value) {
                        $fieldEqualityList[] = $mainTableAlias . "." . $value->getName() . " = " . $childTableAlias . "." . $key;
                        $fieldNullList[] = $childTableAlias . "." . $key . " IS NULL";
                    }
                } else {
                    foreach ($fieldsAsStrings as $field) {
                        $fieldEqualityList[] = $mainTableAlias . "." . $field . " = " . $childTableAlias . "." . $field;
                        $fieldNullList[] = $childTableAlias . "." . $field . " IS NULL";
                    }
                }

                $onClause = join(" AND ", $fieldEqualityList);
                $whereClause = join(" AND ", $fieldNullList);

                return new SQLQuery("*", "(SELECT $mainSelect FROM ({$query->getSQL()}) $mainTableAlias LEFT JOIN ({$childQuery->getSQL()}) $childTableAlias ON $onClause WHERE $whereClause) $wrapperTableAlias", $allParameters);
        }


        return $query;
    }
}