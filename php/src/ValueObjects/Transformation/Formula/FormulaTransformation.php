<?php


namespace Kinintel\ValueObjects\Transformation\Formula;


class FormulaTransformation {

    /**
     * @var Expression[]
     */
    private $expressions;

    /**
     * FormulaTransformation constructor.
     *
     * @param Expression[] $expressions
     */
    public function __construct($expressions = []) {
        $this->expressions = $expressions;
    }

    /**
     * @return Expression[]
     */
    public function getExpressions() {
        return $this->expressions;
    }

    /**
     * @param Expression[] $expressions
     */
    public function setExpressions($expressions) {
        $this->expressions = $expressions;
    }


}