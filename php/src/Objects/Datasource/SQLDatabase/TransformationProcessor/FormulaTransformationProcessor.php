<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use AWS\CRT\Log;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;

class FormulaTransformationProcessor extends SQLTransformationProcessor {


    /**
     * Table aliases for formula expressions
     *
     * @var int
     */
    private $aliasIndex = 0;


    /**
     * Update query with formula values
     *
     * @param FormulaTransformation $transformation
     * @param \Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery $query
     * @param mixed[] $parameterValues
     * @param \Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource $dataSource
     * @return \Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery|void
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        // Update current set of columns if set
        $datasourceColumns = $dataSource->getConfig()->getColumns();
        if (is_array($datasourceColumns) && sizeof($datasourceColumns)) {
            foreach ($transformation->getExpressions() as $expression) {
                $datasourceColumns[] = new Field($expression->returnFieldName(), $expression->getFieldTitle());
            };
            $dataSource->getConfig()->setColumns($datasourceColumns);
        }


        // Gather together the expressions we need.
        $clauses = [];
        $clauseParams = [];
        foreach ($transformation->getExpressions() as $expression) {
            $clauses[] = $expression->returnSQLClause($clauseParams, $parameterValues, $dataSource->returnDatabaseConnection());
        }

        // If Group By, make sure we wrap the query
        if ($query->hasGroupByClause()) {
            $params = array_merge($clauseParams, $query->getParameters());
            $query = new SQLQuery("F" . ++$this->aliasIndex . ".*, " . join(", ", $clauses), "(" . $query->getSQL() . ") F" . $this->aliasIndex, $params);
        } else {
            $query->setSelectClause($query->getSelectClause() . ", " . join(", ", $clauses), $clauseParams);
        }


        return $query;

    }
}