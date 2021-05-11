<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Query\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Query\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Query\FilterQuery;
use Kinintel\ValueObjects\Transformation\Transformation;

class FilterQueryProcessor implements SQLTransformationProcessor {


    /**
     * Update the passed query and return another query
     *
     * @param FilterQuery $transformation
     * @param SQLQuery $query
     * @param Transformation[] $previousTransformationsDescending
     *
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $previousTransformationsDescending = []) {

        $newParams = [];
        $filterSQL = $this->createFilterJunctionStatement($transformation, $newParams);

        $sql = $query->getSql() . " WHERE " . $filterSQL;
        $params = array_merge($query->getParameters(), $newParams);

        return new SQLQuery($sql, $params);

    }


    /**
     * Create the SQL statement for a filter junction
     *
     * @param FilterJunction $filterJunction
     * @param mixed[] $parameters
     */
    private function createFilterJunctionStatement($filterJunction, &$parameters) {

        // Create an array of filter clauses
        $clauses = array_map(function ($filter) use (&$parameters) {
            return $this->createFilterStatement($filter, $parameters);
        }, $filterJunction->getFilters());

        return join(" " . $filterJunction->getLogic() . " ", $clauses);
    }

    /**
     * Create a statement for a single filter
     *
     * @param Filter $filter
     * @param $parameters
     */
    private function createFilterStatement($filter, &$parameters) {

        $fieldName = $filter->getFieldName();
        $filterValue = $filter->getValue();

        $newParams = [];
        if (is_array($filterValue)) {
            $newParams = $filterValue;
        } else if ($filterValue) {
            $newParams = [$filterValue];
        }

        $clause = "";
        switch ($filter->getFilterType()) {
            case Filter::FILTER_TYPE_EQUALS:
                $clause = "$fieldName = ?";
                break;
            case Filter::FILTER_TYPE_NOT_EQUALS:
                $clause = "$fieldName <> ?";
                break;
            case Filter::FILTER_TYPE_NULL:
                $clause = "$fieldName IS NULL";
                $newParams = [];
                break;
            case Filter::FILTER_TYPE_NOT_NULL:
                $clause = "$fieldName IS NOT NULL";
                $newParams = [];
                break;
            case Filter::FILTER_TYPE_GREATER_THAN:
                $clause = "$fieldName > ?";
                break;
            case Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO:
                $clause = "$fieldName >= ?";
                break;
            case Filter::FILTER_TYPE_LESS_THAN:
                $clause = "$fieldName < ?";
                break;
            case Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO:
                $clause = "$fieldName <= ?";
                break;
            case Filter::FILTER_TYPE_LIKE:
                $clause = "$fieldName LIKE ?";
                $newParams = [str_replace("*", "%", $filterValue)];
                break;
            case Filter::FILTER_TYPE_BETWEEN:
                $clause = "$fieldName BETWEEN ? AND ?";
                break;
            case Filter::FILTER_TYPE_IN:
                $clause = "$fieldName IN (" . str_repeat("?,", sizeof($filterValue) - 1) . "?)";
                break;
        }

        array_splice($parameters, sizeof($parameters), 0, $newParams);
        return $clause;
    }

}