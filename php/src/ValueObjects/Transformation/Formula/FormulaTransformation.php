<?php


namespace Kinintel\ValueObjects\Transformation\Formula;


use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class FormulaTransformation implements Transformation, SQLDatabaseTransformation {

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

    /**
     * Get processor key
     *
     * @return string|void
     */
    public function getSQLTransformationProcessorKey() {
        return "formula";
    }

    public function returnAlteredColumns(array $columns): array {
        $expressionFields = array_map(
            fn($expression) => new Field($expression->returnFieldName(), $expression->getFieldTitle()),
            $this->expressions
        );
        return array_merge($columns, $expressionFields);
    }
}