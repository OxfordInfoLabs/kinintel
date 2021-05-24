<?php


namespace Kinintel\ValueObjects\Transformation\Summarise;

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
     * Used to qualify an expression type above
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
     */
    public function __construct($expressionType, $fieldName = null, $customExpression = null) {
        $this->expressionType = $expressionType;
        $this->fieldName = $fieldName;
        $this->customExpression = $customExpression;
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
     * Get the function string for this expression
     *
     * @return string
     */
    public function getFunctionString() {
        if ($this->expressionType == self::EXPRESSION_TYPE_CUSTOM) {
            return $this->customExpression;
        } else if ($this->expressionType == self::EXPRESSION_TYPE_COUNT) {
            return "COUNT(*)";
        } else {
            return $this->expressionType . "(" . $this->fieldName . ")";
        }
    }

}