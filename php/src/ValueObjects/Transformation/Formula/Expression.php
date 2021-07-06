<?php


namespace Kinintel\ValueObjects\Transformation\Formula;


class Expression {


    /**
     * @var string
     */
    private $fieldTitle;

    /**
     * @var string
     */
    private $expression;

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


}