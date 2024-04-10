<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;

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
     * @var SQLValueEvaluator
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
    public function __construct($lhsTableAlias = null, $rhsTableAlias = null, $databaseConnection = null) {
        $this->lhsTableAlias = $lhsTableAlias;
        $this->rhsTableAlias = $rhsTableAlias;
        $this->sqlFilterValueEvaluator = new SQLValueEvaluator($databaseConnection);
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

        $lhsParams = [];
        $rhsParams = [];

        // Map any square brackets to direct columns with table alias or assume whole string is single column
        $lhsExpression = $this->sqlFilterValueEvaluator->evaluateFilterValue($filter->getLhsExpression(), $templateParameters, $this->lhsTableAlias, $lhsParams);
        $rhsExpression = $this->sqlFilterValueEvaluator->evaluateFilterValue($filter->getRhsExpression(), $templateParameters, $this->rhsTableAlias, $rhsParams);


        $clause = "";
        switch ($filter->getFilterType()) {
            case Filter::FILTER_TYPE_NOT_EQUALS:
                $clause = "$lhsExpression <> $rhsExpression";
                break;
            case Filter::FILTER_TYPE_NULL:
                $clause = "$lhsExpression IS NULL";
                $rhsParams = [];
                break;
            case Filter::FILTER_TYPE_NOT_NULL:
                $clause = "$lhsExpression IS NOT NULL";
                $rhsParams = [];
                break;
            case Filter::FILTER_TYPE_GREATER_THAN:
                $clause = "$lhsExpression > $rhsExpression";
                break;
            case Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO:
                $clause = "$lhsExpression >= $rhsExpression";
                break;
            case Filter::FILTER_TYPE_LESS_THAN:
                $clause = "$lhsExpression < $rhsExpression";
                break;
            case Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO:
                $clause = "$lhsExpression <= $rhsExpression";
                break;
            case Filter::FILTER_TYPE_STARTS_WITH:
                $clause = "$lhsExpression LIKE CONCAT($rhsExpression,'%')";
                break;
            case Filter::FILTER_TYPE_ENDS_WITH:
                $clause = "$lhsExpression LIKE CONCAT('%', $rhsExpression)";
                break;
            case Filter::FILTER_TYPE_CONTAINS:
                $clause = "$lhsExpression LIKE CONCAT('%', $rhsExpression, '%')";
                break;
            case Filter::FILTER_TYPE_SIMILAR_TO:
                if (!is_array($rhsParams) || sizeof($rhsParams) !== 2) {
                    throw new DatasourceTransformationException("Filter value for {$filter->getLhsExpression()} must be a two valued array containing a match string and a maximum distance");
                }
                $rhsExpressionComponents = explode(",", $rhsExpression);

                // Add parameters to allow for compound expression.
                $rhsParams = array_merge($lhsParams, $rhsParams, $rhsParams);

                $clause = "(ABS(LENGTH($lhsExpression) - LENGTH($rhsExpressionComponents[0])) <= $rhsExpressionComponents[1]) AND (LEVENSHTEIN($lhsExpression, $rhsExpressionComponents[0]) <= $rhsExpressionComponents[1])";
                break;
            case Filter::FILTER_TYPE_LIKE:
            case Filter::FILTER_TYPE_NOT_LIKE:
                $clause = "$lhsExpression " . ($filter->getFilterType() == Filter::FILTER_TYPE_NOT_LIKE ? "NOT " : "") . "LIKE $rhsExpression";
                if (sizeof($rhsParams))
                    $rhsParams[sizeof($rhsParams) - 1] = str_replace("*", "%", $rhsParams[sizeof($rhsParams) - 1]);
                if (sizeof($lhsParams))
                    $lhsParams[sizeof($lhsParams) - 1] = str_replace("*", "%", $lhsParams[sizeof($lhsParams) - 1]);
                break;

            case Filter::FILTER_TYPE_BETWEEN:
                if (!is_array($rhsParams) || sizeof($rhsParams) !== 2) {
                    throw new DatasourceTransformationException("Filter value for {$filter->getLhsExpression()} must be a two valued array");
                }
                $clause = "$lhsExpression BETWEEN ? AND ?";
                break;
            case Filter::FILTER_TYPE_IN:
                $clause = "$lhsExpression IN (" . $rhsExpression . ")";
                break;
            case Filter::FILTER_TYPE_NOT_IN:
                $clause = "$lhsExpression NOT IN (" . $rhsExpression . ")";
                break;
            default:
                $clause = "$lhsExpression = $rhsExpression";
                break;
        }


        // Add both lhs and rhs params
        if (sizeof($lhsParams))
            array_splice($parameters, sizeof($parameters), 0, $lhsParams);


        if (sizeof($rhsParams))
            array_splice($parameters, sizeof($parameters), 0, $rhsParams);


        return $clause;
    }


}