<?php


namespace Kinintel\ValueObjects\Transformation\Formula;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Util\StringUtils;
use Kinintel\Services\Util\SQLClauseSanitiser;

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
    private $expression;

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
    public function returnSQLClause(&$parameterValues = []) {
        $sqlSanitiser = Container::instance()->get(SQLClauseSanitiser::class);

        // SQL Santise and substitute params
        $expression = $sqlSanitiser->sanitiseSQL($this->expression, $parameterValues);
        $expression = str_replace("]]", "", str_replace("[[", "", $expression));

        return $expression . " " . $this->returnFieldName();
    }

}