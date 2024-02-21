<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Util\ObjectArrayUtils;
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
                $sortStrings[] = $databaseConnection->escapeColumn($sort->getFieldName()) . " " . $sort->getDirection();
            }

            $query->setOrderByClause(join(", ", $sortStrings));
        }
        return $query;
    }
}