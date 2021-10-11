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

        $lhsExpression = $filter->getLhsExpression();
        $rhsExpression = $filter->getRhsExpression();


        // Map any square brackets to direct columns with table alias or assume whole string is single column
        $evaluatedLHS = preg_replace("/\[\[(.*?)\]\]/", ($this->lhsTableAlias ? $this->lhsTableAlias . "." : "") . "$1", $lhsExpression);
        if ($this->lhsTableAlias && $lhsExpression == $evaluatedLHS) {
            $evaluatedLHS = $this->lhsTableAlias . "." . $lhsExpression;
        }
        $lhsExpression = $evaluatedLHS;

        // Map any param values up front using the template mapper
        $newParams = [];
        foreach (is_array($rhsExpression) ? $rhsExpression : [$rhsExpression] as $param) {

            $directParam = $templateParameters[trim($param, "{}")] ?? null;
            if (is_array($directParam)) {
                $newParams = array_merge($newParams, $directParam);
                $rhsExpression = $directParam;
            } else {
                $newParam = $this->sqlFilterValueEvaluator->evaluateFilterValue($param, $templateParameters);
                $newParams[] = $newParam;
            }
        }


        // Remap the filter value back if not an array
        $placeholder = "?";
        if (!is_array($rhsExpression)) {
            $rhsExpression = $newParams[0];

            // Check for [[]] column syntax
            $columnReplaced = preg_replace("/\[\[(.*?)\]\]/", ($this->rhsTableAlias ? $this->rhsTableAlias . "." : "") . "$1", $rhsExpression);
            if ($columnReplaced !== $rhsExpression) {
                $placeholder = $columnReplaced;
                $newParams = [];
            }

        }

        $clause = "";
        $expectArray = false;
        switch ($filter->getFilterType()) {
            case Filter::FILTER_TYPE_NOT_EQUALS:
                $clause = "$lhsExpression <> $placeholder";
                break;
            case Filter::FILTER_TYPE_NULL:
                $clause = "$lhsExpression IS NULL";
                $newParams = [];
                break;
            case Filter::FILTER_TYPE_NOT_NULL:
                $clause = "$lhsExpression IS NOT NULL";
                $newParams = [];
                break;
            case Filter::FILTER_TYPE_GREATER_THAN:
                $clause = "$lhsExpression > $placeholder";
                break;
            case Filter::FILTER_TYPE_GREATER_THAN_OR_EQUAL_TO:
                $clause = "$lhsExpression >= $placeholder";
                break;
            case Filter::FILTER_TYPE_LESS_THAN:
                $clause = "$lhsExpression < $placeholder";
                break;
            case Filter::FILTER_TYPE_LESS_THAN_OR_EQUAL_TO:
                $clause = "$lhsExpression <= $placeholder";
                break;
            case Filter::FILTER_TYPE_LIKE:
                $clause = "$lhsExpression LIKE $placeholder";
                $newParams = [str_replace("*", "%", $rhsExpression)];
                break;
            case Filter::FILTER_TYPE_BETWEEN:
                if (!is_array($rhsExpression) || sizeof($rhsExpression) !== 2) {
                    throw new DatasourceTransformationException("Filter value for $lhsExpression must be a two valued array");
                }
                $expectArray = true;
                $clause = "$lhsExpression BETWEEN ? AND ?";
                break;
            case Filter::FILTER_TYPE_IN:
                $expectArray = true;
                $clause = "$lhsExpression IN (" . str_repeat("?,", sizeof($rhsExpression) - 1) . "?)";
                break;
            case Filter::FILTER_TYPE_NOT_IN:
                $expectArray = true;
                $clause = "$lhsExpression NOT IN (" . str_repeat("?,", sizeof($rhsExpression) - 1) . "?)";
                break;
            default:
                $clause = "$lhsExpression = $placeholder";
                break;
        }


        if ($expectArray && !is_array($rhsExpression)) {
            throw new DatasourceTransformationException("Filter value for $lhsExpression must be an array");
        } else if (!$expectArray && is_array($rhsExpression)) {
            throw new DatasourceTransformationException("Filter value for $lhsExpression should not be an array");
        }

        array_splice($parameters, sizeof($parameters), 0, $newParams);
        return $clause;
    }


}