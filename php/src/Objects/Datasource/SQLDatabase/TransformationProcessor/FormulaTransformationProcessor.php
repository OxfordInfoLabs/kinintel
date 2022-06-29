<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use AWS\CRT\Log;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;

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
        $indexedColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $datasourceColumns);
        if (is_array($datasourceColumns) && sizeof($datasourceColumns)) {
            foreach ($transformation->getExpressions() as $expression) {
                if (!isset($indexedColumns[$expression->returnFieldName()]))
                    $datasourceColumns[] = new Field($expression->returnFieldName(), $expression->getFieldTitle());
            };
            $dataSource->getConfig()->setColumns($datasourceColumns);
        }

        // Check transformations up to this point to see if a substitution is required
        $datasourceTransformations = $dataSource->returnTransformations();
        $transformationIndex = array_search($transformation, $datasourceTransformations);
        $substitutions = [];
        for ($i = $transformationIndex - 1; $i >= 0; $i--) {
            $previousTransformation = $datasourceTransformations[$i];
            // We can stop at summarisations or joins as these will create sub queries anyway.
            if ($previousTransformation instanceof SummariseTransformation || $previousTransformation instanceof JoinTransformation)
                break;
            if ($previousTransformation instanceof FormulaTransformation) {
                foreach ($previousTransformation->getExpressions() as $expression) {
                    $substitutions[$expression->returnFieldName()] = $expression->getExpression();
                }
            }
        }


        // Gather together the expressions we need.
        $clauses = [];
        $clauseParams = [];
        foreach ($transformation->getExpressions() as $expression) {

            // Process any substitutions.
            foreach ($substitutions as $key => $value) {
                $expression->setExpression(str_replace("[[" . $key . "]]", "(" . $value . ")", $expression->getExpression()));
            }

            $clauses[] = $expression->returnSQLClause($clauseParams, $parameterValues, $dataSource->returnDatabaseConnection());
        }

        // If Group By, make sure we wrap the query
        if ($query->hasGroupByClause()) {
            $params = array_merge($clauseParams, $query->getParameters());
            $query = new SQLQuery("F" . ++$this->aliasIndex . ".*, " . join(", ", $clauses), "(" . $query->getSQL() . ") F" . $this->aliasIndex, $params);
        } else {
            $params = array_merge($query->getParametersByClauseType(SQLQuery::SELECT_CLAUSE) ?? [], $clauseParams);
            $query->setSelectClause($query->getSelectClause() . ", " . join(", ", $clauses), $params);
        }


        return $query;

    }
}