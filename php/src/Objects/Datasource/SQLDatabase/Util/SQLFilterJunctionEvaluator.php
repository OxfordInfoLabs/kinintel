<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;

/**
 * Simple evaluator class for evaluating SQL filter junctions to produce SQL clauses
 *
 * Class SQLFilterJunctionEvaluator
 * @package Kinintel\Objects\Datasource\SQLDatabase\Util
 */
class SQLFilterJunctionEvaluator {

    /**
     * @var string
     */
    private $lhsTableAlias = null;

    /**
     * @var string
     */
    private $rhsTableAlias = null;


    /**
     * @var SQLFilterValueEvaluator
     */
    private $sqlFilterValueEvaluator;

    /**
     * Construct optionally with a lhs and rhs table alias if required
     *
     * SQLFilterJunctionEvaluator constructor.
     *
     * @param string $lhsTableAlias
     * @param string $rhsTableAlias
     */
    public function __construct($lhsTableAlias = null, $rhsTableAlias = null) {
        $this->lhsTableAlias = $lhsTableAlias;
        $this->rhsTableAlias = $rhsTableAlias;
        $this->sqlFilterValueEvaluator = new SQLFilterValueEvaluator();
    }


    /**
     * Evaluate the filter junction SQL for the supplied junction.  Returns an associative array
     * containing SQL string and new parameters arising from the junction sql
     *
     * @param FilterJunction $filterJunction
     * @param string $templateParameters
     *
     * @return array
     */
    public function evaluateFilterJunctionSQL($filterJunction, $templateParameters = []) {

        $parameters = [];
        $statement = $this->createFilterJunctionStatement($filterJunction, $parameters, $templateParameters);

        return [
            "sql" => $statement,
            "parameters" => $parameters
        ];
    }

    /**
     * Create the SQL statement for a filter junction
     *
     * @param FilterJunction $filterJunction
     * @param mixed[] $parameters
     */
    private function createFilterJunctionStatement($filterJunction, &$parameters, $templateParameters = []) {

        // Create an array of filter clauses
        $clauses = array_map(function ($filter) use (&$parameters, $templateParameters) {
            return $this->createFilterStatement($filter, $parameters, $templateParameters);
        }, $filterJunction->getFilters());

        $clauses = array_merge($clauses, array_map(function ($junction) use (&$parameters, $templateParameters) {
            return "(" . $this->createFilterJunctionStatement($junction, $parameters, $templateParameters) . ")";
        }, $filterJunction->getFilterJunctions()));

        return join(" " . $filterJunction->getLogic() . " ", $clauses);
    }

    /**
     * Create a statement for a single filter
     *
     * @param Filter $filter
     * @param $parameters
     */
    private function createFilterStatement($filter, &$parameters, $templateParameters = []) {

        $fieldName = $filter->getFieldName();
        $filterValue = $filter->getValue();


        // Map any param values up front using the template mapper
        $newParams = [];
        foreach (is_array($filterValue) ? $filterValue : [$filterValue] as $param) {

            $directParam = $templateParameters[trim($param, "{}")] ?? null;
            if (is_array($directParam)) {
                $newParams = array_merge($newParams, $directParam);
                $filterValue = $directParam;
            } else {
                $newParam = $this->sqlFilterValueEvaluator->evaluateFilterValue($param, $templateParameters);
                $newParams[] = $newParam;
            }
        }


        // Remap the filter value back if not an array
        $placeholder = "?";
        if (!is_array($filterValue)) {
            $filterValue = $newParams[0];

            // Check for [[]] column syntax
            $columnReplaced = preg_replace("/\[\[(.*?)\]\]/", ($this->rhsTableAlias ? $this->rhsTableAlias . "." : "") . "$1", $filterValue);
            if ($columnReplaced !== $filterValue) {
                $placeholder = $columnReplaced;
                $newParams = [];
            }

        }

        $clause = "";
        $expectArray = false;
        switch ($filter->getFilterType()) {
            case Filter::FILTER_TYPE_NOT_EQUALS:
                $clause = "$fieldName <> $placeholder";
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
                $clause = "$fieldName > $placeholder";
                break;
            case Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO:
                $clause = "$fieldName >= $placeholder";
                break;
            case Filter::FILTER_TYPE_LESS_THAN:
                $clause = "$fieldName < $placeholder";
                break;
            case Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO:
                $clause = "$fieldName <= $placeholder";
                break;
            case Filter::FILTER_TYPE_LIKE:
                $clause = "$fieldName LIKE $placeholder";
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
                $clause = "$fieldName = $placeholder";
                break;
        }

        // If a LHS table alias passed through this is pre-pended to the clause
        if ($clause && $this->lhsTableAlias) {
            $clause = $this->lhsTableAlias . "." . $clause;
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