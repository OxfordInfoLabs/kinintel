<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;

use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;


class FilterTransformationProcessor implements SQLTransformationProcessor {


    /**
     * @var TemplateParser
     */
    private $templateParser;


    /**
     * FilterTransformationProcessor constructor.
     *
     * @param TemplateParser $templateParser
     */
    public function __construct($templateParser) {
        $this->templateParser = $templateParser;
    }

    /**
     * Update the passed query and return another query
     *
     * @param FilterTransformation $transformation
     * @param SQLQuery $query
     * @param mixed[] $parameterValues
     * @param $dataSource
     *
     * @return SQLQuery
     */
    public function updateQuery($transformation, $query, $parameterValues, $dataSource) {

        $newParams = [];
        $filterSQL = $this->createFilterJunctionStatement($transformation, $newParams, $parameterValues);


        if ($query->hasGroupByClause()) {
            $query->setHavingClause($filterSQL, $newParams);
        } else {
            $query->setWhereClause($filterSQL, $newParams);
        }

        return $query;

    }


    /**
     * Create the SQL statement for a filter junction
     *
     * @param FilterJunction $filterJunction
     * @param mixed[] $parameters
     */
    private function createFilterJunctionStatement($filterJunction, &$parameters, $parameterValues = []) {

        // Create an array of filter clauses
        $clauses = array_map(function ($filter) use (&$parameters, $parameterValues) {
            return $this->createFilterStatement($filter, $parameters, $parameterValues);
        }, $filterJunction->getFilters());

        $clauses = array_merge($clauses, array_map(function ($junction) use (&$parameters, $parameterValues) {
            return "(" . $this->createFilterJunctionStatement($junction, $parameters, $parameterValues) . ")";
        }, $filterJunction->getFilterJunctions()));

        return join(" " . $filterJunction->getLogic() . " ", $clauses);
    }

    /**
     * Create a statement for a single filter
     *
     * @param Filter $filter
     * @param $parameters
     */
    private function createFilterStatement($filter, &$parameters, $parameterValues = []) {

        $fieldName = $filter->getFieldName();
        $filterValue = $filter->getValue();

        // Map any param values up front using the template mapper
        $newParams = [];
        foreach (is_array($filterValue) ? $filterValue : [$filterValue] as $param) {

            $directParam = $parameterValues[trim($param, "{}")] ?? null;
            if (is_array($directParam)) {
                $newParams = array_merge($newParams, $directParam);
                $filterValue = $directParam;
            } else {
                $newParam = $this->templateParser->parseTemplateText($param, $parameterValues);
                $newParams[] = $newParam;
            }
        }

        // Remap the filter value back if not an array
        if (!is_array($filterValue)) {
            $filterValue = $newParams[0];
        }

        $clause = "";
        $expectArray = false;
        switch ($filter->getFilterType()) {
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
                if (!is_array($filterValue) || sizeof($filterValue) !== 2) {
                    throw new DatasourceTransformationException("Filter value for $fieldName must be a two valued array");
                }
                $expectArray = true;
                $clause = "$fieldName BETWEEN ? AND ?";
                break;
            case Filter::FILTER_TYPE_IN:
                $expectArray = true;
                $clause = "$fieldName IN (" . str_repeat("?,", sizeof($filterValue) - 1) . "?)";
                break;
            case Filter::FILTER_TYPE_NOT_IN:
                $expectArray = true;
                $clause = "$fieldName NOT IN (" . str_repeat("?,", sizeof($filterValue) - 1) . "?)";
                break;
            default:
                $clause = "$fieldName = ?";
                break;
        }

        if ($expectArray && !is_array($filterValue)) {
            throw new DatasourceTransformationException("Filter value for $fieldName must be an array");
        } else if (!$expectArray && is_array($filterValue)) {
            throw new DatasourceTransformationException("Filter value for $fieldName should not be an array");
        }

        array_splice($parameters, sizeof($parameters), 0, $newParams);
        return $clause;
    }

}