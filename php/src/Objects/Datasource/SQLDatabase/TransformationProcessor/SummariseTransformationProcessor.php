<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
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


            // If we already have a group by clause or have explicit columns we need to create a query wrapper
            if ($query->hasGroupByClause() || $query->getSelectClause() !== "*") {
                $query = new SQLQuery("*", "(" . $query->getSQL() . ") S" . ++$this->aliasIndex, $query->getParameters());
            }

            $groupByClauses = $transformation->getSummariseFieldNames();
            $evaluatedExpressions = [];
            $clauseParameters = [];
            foreach ($transformation->getExpressions() as $expression) {
                $evaluatedExpressions[] = $expression->getFunctionString($clauseParameters, $parameterValues, $dataSource->returnDatabaseConnection());
            }
            $evaluatedExpressions = array_merge($groupByClauses, $evaluatedExpressions);
            if (sizeof($groupByClauses))
                $query->setGroupByClause(join(", ", $evaluatedExpressions), join(", ", $groupByClauses), [], $clauseParameters);
            else if (sizeof($evaluatedExpressions))
                $query->setSelectClause(join(", ", $evaluatedExpressions), $clauseParameters);
        }

        return $query;
    }
}