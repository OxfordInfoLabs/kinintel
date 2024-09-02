<?php

namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinintel\Objects\Datasource\Datasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Pivot\PivotTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class PivotTransformationProcessor extends SQLTransformationProcessor {

    private int $aliasIndex = 0;

    /**
     * @param Transformation $transformation
     * @param Datasource $datasource
     * @param array<string, string> $parameterValues
     * @param PagingTransformation $pagingTransformation
     * @return Datasource
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = [], $pagingTransformation = null): Datasource {
        return $datasource;
    }

    /**
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param array<string, string> $parameterValues
     * @param Datasource $dataSource
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource): SQLQuery {

        if ($transformation instanceof PivotTransformation) {

            // If we already have a group by clause or have explicit columns we need to create a query wrapper
            if ($query->hasGroupByClause() || $query->getSelectClause() !== "*") {
                $query = new SQLQuery("*", "(" . $query->getSQL() . ") S" . ++$this->aliasIndex, $query->getParameters());
            }

            $columns = [];
            $databaseConnection = $dataSource->returnDatabaseConnection();

            $summariseFieldNames = $transformation->getSummariseColumns();
            $groupByClauses = [];
            foreach ($summariseFieldNames as $summariseFieldName) {
                $groupByClauses[] = $databaseConnection->escapeColumn($summariseFieldName);
                $columns[] = new Field($summariseFieldName);
            }

            $evaluatedExpressions = [];
            $clauseParameters = [];
            foreach ($transformation->getExpressions() as $expression) {
                $expressionParams = [];
                $evaluatedExpressions[] = $expression->returnSQLClause($clauseParameters, $parameterValues, $databaseConnection);
                $clauseParameters = array_merge($clauseParameters, $expressionParams);
                $columns[] = new Field($expression->returnFieldName());
            }


            $evaluatedExpressions = array_merge($groupByClauses, $evaluatedExpressions);

            if (sizeof($groupByClauses))
                $query->setGroupByClause(join(", ", $evaluatedExpressions), join(", ", $groupByClauses), [], $clauseParameters);
            else if (sizeof($evaluatedExpressions))
                $query->setSelectClause(join(", ", $evaluatedExpressions), $clauseParameters);


            // Set columns
            $dataSource->getConfig()->setColumns($columns);

        }

        return $query;
    }
}