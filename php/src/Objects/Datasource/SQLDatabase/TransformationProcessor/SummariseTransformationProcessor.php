<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class SummariseTransformationProcessor extends SQLTransformationProcessor {


    /**
     * Unset any columns
     *
     * @param $transformation
     * @param $datasource
     * @param array $parameterValues
     * @return \Kinintel\Objects\Datasource\Datasource|void
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = []) {

        // Unset any explicit columns from the datasource config
        $datasource->getConfig()->setColumns([]);

        return $datasource;
    }


    /**
     * Update a query object for a passed transformation.
     *
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param SQLDatabaseDatasource $dataSource
     * @return SQLQuery|void
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        $dataSource->getConfig()->setColumns([]);

        if ($transformation instanceof SummariseTransformation) {
            $groupByClauses = $transformation->getSummariseFieldNames();
            $evaluatedExpressions = [];
            foreach ($transformation->getExpressions() as $expression) {
                $evaluatedExpressions[] = $expression->getFunctionString();
            }
            $evaluatedExpressions = array_merge($groupByClauses, $evaluatedExpressions);
            if (sizeof($groupByClauses))
                $query->setGroupByClause(join(", ", $evaluatedExpressions), join(", ", $groupByClauses));
            else if (sizeof($evaluatedExpressions))
                $query->setSelectClause(join(", ", $evaluatedExpressions));
        }

        return $query;
    }
}