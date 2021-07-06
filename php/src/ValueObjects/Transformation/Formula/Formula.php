<?php


namespace Kinintel\ValueObjects\Transformation\Formula;


class Formula {

    /**
     * @var Expression[]
     */
    private $expressions;

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