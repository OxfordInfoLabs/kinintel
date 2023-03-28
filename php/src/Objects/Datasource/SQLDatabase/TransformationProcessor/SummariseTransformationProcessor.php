<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class SummariseTransformationProcessor extends SQLTransformationProcessor {

    /**
     * Alias index
     *
     * @var int
     */
    private $aliasIndex = 0;


    /**
     * Unset any columns
     *
     * @param $transformation
     * @param $datasource
     * @param array $parameterValues
     * @param null $pagingTransformation
     * @return \Kinintel\Objects\Datasource\Datasource|void
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = [], $pagingTransformation = null) {

        // Unset any explicit columns from the datasource config
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

        if ($transformation instanceof SummariseTransformation) {

            // If we already have a group by clause or have explicit columns we need to create a query wrapper
            if ($query->hasGroupByClause() || $query->getSelectClause() !== "*") {
                $query = new SQLQuery("*", "(" . $query->getSQL() . ") S" . ++$this->aliasIndex, $query->getParameters());
            }

            $columns = [];
            $databaseConnection = $dataSource->returnDatabaseConnection();

            $summariseFieldNames = $transformation->getSummariseFieldNames();
            $groupByClauses = [];
            foreach ($summariseFieldNames as $summariseFieldName) {
                $groupByClauses[] = $databaseConnection->escapeColumn($summariseFieldName);
                $columns[] = new Field($summariseFieldName);
            }

            $evaluatedExpressions = [];
            $clauseParameters = [];
            foreach ($transformation->getExpressions() as $expression) {
                $expressionParams = [];
                $evaluatedExpressions[] = $expression->getFunctionString($expressionParams, $parameterValues, $databaseConnection);
                $clauseParameters = array_merge($clauseParameters, $expressionParams);
                $columns[] = new Field($expression->getCustomFieldName($databaseConnection));
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