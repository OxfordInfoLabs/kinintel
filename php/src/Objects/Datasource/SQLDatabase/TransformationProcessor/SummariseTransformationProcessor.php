<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class SummariseTransformationProcessor extends SQLTransformationProcessor {

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

        if ($transformation instanceof SummariseTransformation) {
            $groupByClauses = $transformation->getSummariseFieldNames();
            $evaluatedExpressions = [];
            foreach ($transformation->getExpressions() as $expression) {
                $evaluatedExpressions[] = $expression->getFunctionString();
            }
            $evaluatedExpressions = array_merge($groupByClauses, $evaluatedExpressions);
            $query->setGroupByClause(join(", ", $evaluatedExpressions), join(", ", $groupByClauses));

            // Unset any explicit columns from the datasource config
            $dataSource->getConfig()->setColumns([]);

        }

        return $query;
    }
}