<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;

class FormulaTransformationProcessor extends SQLTransformationProcessor {


    /**
     * Apply transformation
     *
     * @param FormulaTransformation $transformation
     * @param SQLDatabaseDatasource $datasource
     * @param mixed[] $parameterValues
     * @return \Kinintel\Objects\Datasource\Datasource|void
     */
    public function applyTransformation($transformation, $datasource, $parameterValues = []) {

        // Update current set of columns if set
//        $datasourceColumns = $datasource->getConfig()->getColumns();
//        if (is_array($datasourceColumns) && sizeof($datasourceColumns)) {
//            foreach ($transformation->getExpressions() as $expression) {
//                $datasourceColumns[] = new Field($expression->returnFieldName(), $expression->getFieldTitle());
//            }
//            $datasource->getConfig()->setColumns($datasourceColumns);
//        }

        return $datasource;
    }


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
            }
            $dataSource->getConfig()->setColumns($datasourceColumns);
        }


        // Gather together the expressions we need.
        $clauses = [];
        foreach ($transformation->getExpressions() as $expression) {
            $clauses[] = $expression->returnSQLClause();
        }

        $query->setSelectClause($query->getSelectClause() . ", " . join(", ", $clauses));



        return $query;

    }
}