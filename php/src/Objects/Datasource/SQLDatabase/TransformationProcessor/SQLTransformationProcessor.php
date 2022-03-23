<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Transformation;

/**
 *
 * @implementation filter Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\FilterTransformationProcessor
 * @implementation multisort Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\MultiSortTransformationProcessor
 * @implementation paging Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\PagingTransformationProcessor
 * @implementation summarise Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SummariseTransformationProcessor
 * @implementation join Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\JoinTransformationProcessor
 * @implementation columns Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\ColumnsTransformationProcessor
 * @implementation formula Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\FormulaTransformationProcessor
 */
abstract class SQLTransformationProcessor {


    /**
     * @param $transformation
     * @param $datasource
     * @param $parameterValues
     * @param null $pagingTransformation
     *
     * @return Datasource
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = [], $pagingTransformation = null) {
        return $datasource;
    }


    /**
     * Modify the passed SQL to apply the transformation, return modified sql string.
     *
     * The previous transformation (if available) is supplied as this may affect behaviour
     *
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param SQLDatabaseDatasource $dataSource
     *
     * @return SQLQuery
     */
    public abstract function updateQuery($transformation, $query, $parameterValues, $dataSource);

}