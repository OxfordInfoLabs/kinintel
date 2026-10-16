<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;

use Kinikit\Core\Logging\Logger;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\PreparedStatement\BindParameter;
use Kinikit\Persistence\Database\PreparedStatement\BindParameterType;
use Kinintel\Exception\DatasourceTransformationException;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;

/**
 * Simple evaluator class for evaluating SQL filter junctions to produce SQL clauses
 *
 * Class SQLFilterJunctionEvaluator
 * @package Kinintel\Objects\Datasource\SQLDatabase\Util
 */
class SQLFilterJunctionEvaluator {

    private ?string $lhsTableAlias = null;

    private ?string $rhsTableAlias = null;

    private SQLValueEvaluator $sqlFilterValueEvaluator;

    /**
     * Construct optionally with a lhs and rhs table alias if required
     *
     * SQLFilterJunctionEvaluator constructor.
     *
     * @param string|null $lhsTableAlias
     * @param string|null $rhsTableAlias
     * @param DatabaseConnection|null $databaseConnection
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
    public function evaluateFilterJunctionSQL($filterJunction, $templateParameters = [], $lhsFields = [], $rhsFields = []) {

        $parameters = [];
        $statement = $this->createFilterJunctionStatement($filterJunction, $parameters, $templateParameters, $lhsFields, $rhsFields);

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
    private function createFilterJunctionStatement($filterJunction, &$parameters, $templateParameters = [], $lhsFields = [], $rhsFields = []) {

        if ($filterJunction->meetsInclusionCriteria($templateParameters)) {

            // Create an array of filter clauses
            $clauses = [];
            foreach ($filterJunction->getFilters() ?? [] as $filter) {
                if ($filter->meetsInclusionCriteria($templateParameters)) {
                    $statement = $this->createFilterStatement($filter, $parameters, $templateParameters, $lhsFields, $rhsFields);
                    if (trim($statement))
                        $clauses[] = $statement;
                }
            };

            foreach ($filterJunction->getFilterJunctions() ?? [] as $junction) {
                if ($junction->meetsInclusionCriteria($templateParameters)) {
                    $statement = $this->createFilterJunctionStatement($junction, $parameters, $templateParameters, $lhsFields, $rhsFields);
                    if (trim($statement))
                        $clauses[] = "(" . $statement . ")";
                }

            }


            return trim(join(" " . $filterJunction->getLogic()->name . " ", $clauses));
        } else {
            return "";
        }
    }

    /**
     * Create a statement for a single filter
     *
     * @param Filter $filter
     * @param $parameters
     */
    private function createFilterStatement($filter, &$parameters, $templateParameters = [], $lhsFields = [], $rhsFields = []) {

        $lhsParams = [];
        $rhsParams = [];


        $lhsBindType = $this->getBindTypeForExpression($filter->getLhsExpression(), $lhsFields);
        $rhsBindType = $this->getBindTypeForExpression($filter->getRhsExpression(), $rhsFields);

        // Map any square brackets to direct columns with table alias or assume whole string is single column
        $lhsExpression = $this->sqlFilterValueEvaluator->evaluateFilterValue(
            $filter->getLhsExpression(),
            $templateParameters,
            $this->lhsTableAlias,
            $lhsParams,
            $rhsBindType
        );
        $rhsExpression = $this->sqlFilterValueEvaluator->evaluateFilterValue(
            $filter->getRhsExpression(),
            $templateParameters,
            $this->rhsTableAlias,
            $rhsParams,
            $lhsBindType
        );
        $rhsExpressionComponents = explode(",", $rhsExpression);

        $clause = "";
        switch ($filter->getFilterType()) {
            case FilterType::neq:
                $clause = "$lhsExpression <> $rhsExpression";
                break;
            case FilterType::null:
            case FilterType::isnull:
                $clause = "$lhsExpression IS NULL";
                $rhsParams = [];
                break;
            case FilterType::notnull:
            case FilterType::isnotnull:
                $clause = "$lhsExpression IS NOT NULL";
                $rhsParams = [];
                break;
            case FilterType::gt:
                $clause = "$lhsExpression > $rhsExpression";
                break;
            case FilterType::gte:
                $clause = "$lhsExpression >= $rhsExpression";
                break;
            case FilterType::lt:
                $clause = "$lhsExpression < $rhsExpression";
                break;
            case FilterType::lte:
                $clause = "$lhsExpression <= $rhsExpression";
                break;
            case FilterType::bitwiseor:
                $clause = "$lhsExpression | $rhsExpression";
                break;
            case FilterType::bitwiseand:
                $clause = "$lhsExpression & $rhsExpression";
                break;
            case FilterType::startswith:
                $clause = "$lhsExpression LIKE CONCAT($rhsExpression,'%')";
                break;
            case FilterType::endswith:
                $clause = "$lhsExpression LIKE CONCAT('%', $rhsExpression)";
                break;
            case FilterType::contains:
                $clause = "$lhsExpression LIKE CONCAT('%', $rhsExpression, '%')";
                break;
            case FilterType::similarto:

                if (!is_array($rhsExpressionComponents) || sizeof($rhsExpressionComponents) !== 2) {
                    throw new DatasourceTransformationException("Filter value for {$filter->getLhsExpression()} must be a two valued array containing a match string and a maximum distance");
                }

                // Add parameters to allow for compound expression.
                $rhsParams = array_merge($lhsParams, $rhsParams, $rhsParams);

                $clause = "(ABS(LENGTH($lhsExpression) - LENGTH($rhsExpressionComponents[0])) <= $rhsExpressionComponents[1]) AND (LEVENSHTEIN($lhsExpression, $rhsExpressionComponents[0]) <= $rhsExpressionComponents[1])";
                break;
            case FilterType::like:
            case FilterType::notlike:

                $last = $rhsParams[count($rhsParams ?? []) - 1] ?? null;

                $regexp = match ($last) {
                    Filter::LIKE_MATCH_REGEXP => true,
                    default => false
                };

                // * will work when you only have one LHS or RHS parameter (e.g. not with CONCAT(?, ?))
                if (!$regexp && sizeof($rhsParams))
                    $rhsParams[0] = str_replace("*", "%", $rhsParams[0]);
                if (!$regexp && sizeof($lhsParams))
                    $lhsParams[0] = str_replace("*", "%", $lhsParams[0]);

                $likeKeyword = $regexp ? "RLIKE" : "LIKE";

                if ($last == Filter::LIKE_MATCH_REGEXP || $last == Filter::LIKE_MATCH_WILDCARD) {
                    array_pop($rhsParams);
                    array_pop($rhsExpressionComponents);
                }

                $clause = "$lhsExpression " . ($filter->getFilterType() == FilterType::notlike ? "NOT " : "") . "$likeKeyword " . join(",", $rhsExpressionComponents);

                break;

            case FilterType::between:
                if (!is_array($rhsExpressionComponents) || sizeof($rhsExpressionComponents) !== 2) {
                    throw new DatasourceTransformationException("Filter value for {$filter->getLhsExpression()} must be a two valued array");
                }
                $clause = "$lhsExpression BETWEEN ? AND ?";
                break;
            case FilterType::in:
                if (trim($rhsExpression))
                    $clause = "$lhsExpression IN (" . $rhsExpression . ")";
                break;
            case FilterType::notin:
                if (trim($rhsExpression))
                    $clause = "$lhsExpression NOT IN (" . $rhsExpression . ")";
                break;
            default:
                $clause = "$lhsExpression = $rhsExpression";
                break;
        }

        if ($filter->getFilterType() !== FilterType::similarto) {
            $lhsParams = $this->typeParameters($lhsParams, $rhsBindType);
            $rhsParams = $this->typeParameters($rhsParams, $lhsBindType);
        }

        // Add both lhs and rhs params
        if (sizeof($lhsParams))
            array_splice($parameters, sizeof($parameters), 0, $lhsParams);


        if (sizeof($rhsParams))
            array_splice($parameters, sizeof($parameters), 0, $rhsParams);


        return $clause;
    }

    private function getBindTypeForExpression($expression, $fields): ?BindParameterType {
        if (is_array($expression) && count($expression) === 1) {
            $expression = reset($expression);
        }

        if (!is_string($expression) || !preg_match('/^\s*\[\[(.*?)\]\]\s*$/', $expression, $matches)) {
            return null;
        }

        $fieldName = $matches[1];
        foreach ($fields as $field) {
            if ($field instanceof Field && $field->getName() === $fieldName) {
                return match ($field->getType()) {
                    Field::TYPE_INTEGER, Field::TYPE_BIGINTEGER, Field::TYPE_ID => BindParameterType::TYPE_INT,
                    Field::TYPE_FLOAT => BindParameterType::TYPE_FLOAT,
                    Field::TYPE_BOOLEAN => BindParameterType::TYPE_BOOL,
                    Field::TYPE_DATE => BindParameterType::TYPE_DATE,
                    Field::TYPE_DATE_TIME => BindParameterType::TYPE_DATETIME,
                    Field::TYPE_JSON => BindParameterType::TYPE_ARRAY,
                    default => BindParameterType::TYPE_STRING
                };
            }
        }

        return null;
    }

    private function typeParameters(array $parameters, ?BindParameterType $type): array {
        if ($type === null) {
            return $parameters;
        }

        return array_map(function ($parameter) use ($type) {
            if ($parameter instanceof BindParameter) {
                return $parameter;
            }

            if ($parameter === null) {
                return new BindParameter(null, $type);
            }

            $value = match ($type) {
                BindParameterType::TYPE_INT => filter_var($parameter, FILTER_VALIDATE_INT) !== false
                    ? (int)$parameter
                    : null,
                BindParameterType::TYPE_FLOAT => is_numeric($parameter) ? (float)$parameter : null,
                BindParameterType::TYPE_BOOL => filter_var($parameter, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                BindParameterType::TYPE_STRING, BindParameterType::TYPE_DATE, BindParameterType::TYPE_DATETIME => is_scalar($parameter)
                    ? (string)$parameter
                    : null,
                BindParameterType::TYPE_ARRAY => is_array($parameter) ? $parameter : null,
                default => null
            };

            return $value === null
                ? BindParameter::create($parameter)
                : new BindParameter($value, $type);
        }, $parameters);
    }


}