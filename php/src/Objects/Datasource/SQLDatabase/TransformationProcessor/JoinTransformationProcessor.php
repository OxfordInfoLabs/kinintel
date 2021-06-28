<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;

class JoinTransformationProcessor implements SQLTransformationProcessor {

    /**
     * Update a SQL query object for a join transformation.
     *
     * @param JoinTransformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param $dataSource
     *
     * @return SQLQuery|void
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {



    }
}