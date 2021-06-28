<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class MultiSortTransformationProcessor implements SQLTransformationProcessor {

    /**
     * Update the query with
     *
     * @param Transformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $parameterValues = []) {
        if ($transformation instanceof MultiSortTransformation) {
            $sortStrings = ObjectArrayUtils::getMemberValueArrayForObjects("sortString", $transformation->getSorts());
            $query->setOrderByClause(join(", ", $sortStrings));
        }
        return $query;
    }
}