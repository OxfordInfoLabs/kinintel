<?php


namespace Kinintel\ValueObjects\Transformation\Summarise;

use Kinikit\Core\Util\StringUtils;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLValueEvaluator;

/**
 * Capture a summarise expression
 *
 * Class SummariseExpression
 *
 * @package Kinintel\ValueObjects\Transformation\Summarise
 */
class SummariseExpression {

    /**
     * @var string
     *
     */
    private $expressionType;

    /**
     * Used to qualify an expression type above if not custom
     *
     * @var string
     */
    private $fieldName;

    /**
     * Used with type custom only
     *
     * @var string
     */
    private $customExpression;

    /**
     * Used to set a custom label - required if type is custom
     *
     * @var string
     */
    private $customLabel;


    // Expression constants
    const EXPRESSION_TYPE_COUNT = "COUNT";
    const EXPRESSION_TYPE_SUM = "SUM";
    const EXPRESSION_TYPE_MIN = "MIN";
    const EXPRESSION_TYPE_MAX = "MAX";
    const EXPRESSION_TYPE_AVG = "AVG";
    const EXPRESSION_TYPE_CUSTOM = "CUSTOM";

    /**
     * SummariseExpression constructor.
     *
     * @param string $expressionType
     * @param string $fieldName
     * @param string $customExpression
     * @param string $customLabel
     */
    public function __construct($expressionType, $fieldName = null, $customExpression = null, $customLabel = null) {
        $this->expressionType = $expressionType;
        $this->fieldName = $fieldName;
        $this->customExpression = $customExpression;
        $this->customLabel = $customLabel;
    }


    /**
     * @return string
     */
    public function getExpressionType() {
        return $this->expressionType;
    }

    /**
     * @param string $expressionType
     */
    public function setExpressionType($expressionType) {
        $this->expressionType = $expressionType;
    }

    /**
     * @return string
     */
    public function getFieldName() {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getCustomExpression() {
        return $this->customExpression;
    }

    /**
     * @param string $customExpression
     */
    public function setCustomExpression($customExpression) {
        $this->customExpression = $customExpression;
    }

    /**
     * @return string
     */
    public function getCustomLabel() {
        return $this->customLabel;
    }

    /**
     * @param string $customLabel
     */
    public function setCustomLabel($customLabel) {
        $this->customLabel = $customLabel;
    }


    /**
     * Get a custom field name as conversion from label
     */
    public function getCustomFieldName() {
        return StringUtils::convertToCamelCase($this->customLabel);
    }


    /**
     * Get the function string for this expression
     *
     * @return string
     */
    public function getFunctionString(&$clauseParameters, $parameterValues = [], $databaseConnection = null) {
        if ($this->expressionType == self::EXPRESSION_TYPE_CUSTOM) {
            $function = $this->customExpression;
        } else if ($this->expressionType == self::EXPRESSION_TYPE_COUNT) {
            $function = "COUNT(*)";
        } else {
            $function = $this->expressionType . "([[" . $this->fieldName . "]])";
        }

        /**
         * @var SQLValueEvaluator $sqlValueEvaluator
         */
        $sqlValueEvaluator = new SQLValueEvaluator($databaseConnection);
        $function = $sqlValueEvaluator->evaluateFilterValue($function, $parameterValues, null, $clauseParameters);

        if ($this->customLabel) {
            $function .= " " . $databaseConnection->escapeColumn($this->getCustomFieldName($databaseConnection));
        }

        return $function;

    }


}