<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class SummariseTransformationProcessor implements SQLTransformationProcessor {

    /**
     * Update a query object for a passed transformation.
     *
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param array $previousTransformationsDescending
     * @return SQLQuery|void
     */
    public function updateQuery($transformation, $query, $previousTransformationsDescending = []) {

        if ($transformation instanceof SummariseTransformation) {
            $groupByClauses = $transformation->getSummariseFieldNames();
            $evaluatedExpressions = [];
            foreach ($transformation->getExpressions() as $expression) {
                $evaluatedExpressions[] = $expression->getFunctionString();
            }
            $evaluatedExpressions = array_merge($groupByClauses, $evaluatedExpressions);
            $query->setGroupByClause(join(", ", $evaluatedExpressions), join(", ", $groupByClauses));
        }

        return $query;
    }
}