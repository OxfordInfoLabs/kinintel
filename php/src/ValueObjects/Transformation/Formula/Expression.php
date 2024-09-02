<?php


namespace Kinintel\ValueObjects\Transformation\Formula;


use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLValueEvaluator;

class Expression {


    /**
     * @var string
     * @required
     */
    private $fieldTitle;

    /**
     * @var string
     * @required
     */
    protected $expression;

    /**
     * Expression constructor.
     *
     * @param string $fieldTitle
     * @param string $expression
     */
    public function __construct($fieldTitle, $expression) {
        $this->fieldTitle = $fieldTitle;
        $this->expression = $expression;
    }


    /**
     * @return string
     */
    public function getFieldTitle() {
        return $this->fieldTitle;
    }

    /**
     * @param string $fieldTitle
     */
    public function setFieldTitle($fieldTitle) {
        $this->fieldTitle = $fieldTitle;
    }

    /**
     * @return string
     */
    public function getExpression() {
        return $this->expression;
    }

    /**
     * @param string $expression
     */
    public function setExpression($expression) {
        $this->expression = $expression;
    }


    /**
     * Return the derived field name
     *
     * @return string
     */
    public function returnFieldName() {
        return StringUtils::convertToCamelCase($this->fieldTitle);
    }

    // Return SQL clause
    public function returnSQLClause(&$clauseParams, $parameterValues, $databaseConnection) {

        $sqlValueEvaluator = new SQLValueEvaluator($databaseConnection);

        // SQL Santise and substitute params
        $sanitisedParams = [];
        $expression = $sqlValueEvaluator->evaluateFilterValue($this->expression, $parameterValues, null, $sanitisedParams);
        $clauseParams = array_merge($clauseParams, $sanitisedParams);

        return $expression . " " . $this->returnFieldName();
    }

}