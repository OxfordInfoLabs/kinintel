<?php

namespace Kinintel\ValueObjects\Transformation\Pivot;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLValueEvaluator;
use Kinintel\ValueObjects\Transformation\Formula\Expression;

class PivotExpression extends Expression {

    /**
     * @param string $fieldTitle
     * @param string $expression
     */
    public function __construct(string $fieldTitle, string $expression) {
        parent::__construct($fieldTitle, $expression);
    }

    /**
     * @param mixed $clauseParams
     * @param mixed $parameterValues
     * @param DatabaseConnection $databaseConnection
     * @return string
     */
    public function returnSQLClause(&$clauseParams, $parameterValues, $databaseConnection): string {

        $sqlValueEvaluator = new SQLValueEvaluator($databaseConnection);

        // SQL Santise and substitute params
        $sanitisedParams = [];
        $expression = $sqlValueEvaluator->evaluateFilterValue($this->expression, $parameterValues, null, $sanitisedParams);
        $clauseParams = array_merge($clauseParams, $sanitisedParams);

        return $expression . " " . $databaseConnection->escapeColumn($this->returnFieldName());
    }
}