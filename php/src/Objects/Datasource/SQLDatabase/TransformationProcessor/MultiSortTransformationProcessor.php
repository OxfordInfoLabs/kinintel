<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Logging\Logger;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class MultiSortTransformationProcessor extends SQLTransformationProcessor {

    /**
     * Update the query with
     *
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param $dataSource
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {
        if ($transformation instanceof MultiSortTransformation) {

            $databaseConnection = $dataSource->returnDatabaseConnection();

            $sortStrings = [];
            foreach ($transformation->getSorts() as $sort) {
                if ($sort->meetsInclusionCriteria($parameterValues))
                    $sortStrings[] = $databaseConnection->escapeColumn($sort->getFieldName()) . " " . $sort->getDirection();
            }

            if (sizeof($sortStrings))
                $query->setOrderByClause(join(", ", $sortStrings));
        }
        return $query;
    }
}