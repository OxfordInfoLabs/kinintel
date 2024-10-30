<?php


namespace Kinintel\ValueObjects\Transformation\Summarise;


use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class SummariseTransformation implements Transformation, SQLDatabaseTransformation {

    /**
     * Which fields to summarise based upon
     *
     * @var string[]
     */
    private $summariseFieldNames;

    /**
     * Summarise expression array
     *
     * @var SummariseExpression[]
     */
    private $expressions;

    /**
     * SummariseTransformation constructor.
     *
     * @param string[] $summariseFieldNames
     * @param SummariseExpression[] $expressions
     */
    public function __construct($summariseFieldNames = [], $expressions = []) {
        $this->summariseFieldNames = $summariseFieldNames;
        $this->expressions = $expressions;
    }


    /**
     * @return string[]
     */
    public function getSummariseFieldNames() {
        return $this->summariseFieldNames;
    }

    /**
     * @param string[] $summariseFieldNames
     */
    public function setSummariseFieldNames($summariseFieldNames) {
        $this->summariseFieldNames = $summariseFieldNames;
    }

    /**
     * @return SummariseExpression[]
     */
    public function getExpressions() {
        return $this->expressions;
    }

    /**
     * @param SummariseExpression[] $expressions
     */
    public function setExpressions($expressions) {
        $this->expressions = $expressions;
    }


    public function getSQLTransformationProcessorKey() {
        return "summarise";
    }

    public function returnAlteredColumns(array $columns): array {
        $summariseOverFields = array_map(fn($fieldName) => new Field($fieldName), $this->summariseFieldNames);
        $expressionFields = array_map(fn($expression) => $expression->toField(), $this->expressions);
        return array_merge($summariseOverFields, $expressionFields);
    }
}