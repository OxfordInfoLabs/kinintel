<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Logging\Logger;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class PagingTransformationProcessor extends SQLTransformationProcessor {

    /**
     * Update query
     *
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param $dataSource
     * @return SQLQuery|void
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {
        if ($transformation instanceof PagingTransformation) {

            if ($transformation->getLimit() !== null && is_numeric($transformation->getLimit())) {
                $query->setLimit($transformation->getLimit());
            }
            
            if ($transformation->getOffset() !== null && is_numeric($transformation->getOffset())) {
                $query->setOffset($transformation->getOffset());
            }
        }

        return $query;
    }
}