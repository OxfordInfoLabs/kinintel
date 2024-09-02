<?php

namespace Kinintel\ValueObjects\Transformation\Pivot;

use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class PivotTransformation implements Transformation, SQLDatabaseTransformation {

    /**
     * @param string[] $summariseColumns
     * @param PivotExpression[] $expressions
     */
    public function __construct(
        private array $summariseColumns,
        private array $expressions
    ) {
    }

    /**
     * @return string[]
     */
    public function getSummariseColumns(): array {
        return $this->summariseColumns;
    }

    /**
     * @param string[] $summariseColumns
     * @return void
     */
    public function setSummariseColumns(array $summariseColumns): void {
        $this->summariseColumns = $summariseColumns;
    }

    /**
     * @return PivotExpression[]
     */
    public function getExpressions(): array {
        return $this->expressions;
    }

    /**
     * @param PivotExpression[] $expressions
     * @return void
     */
    public function setExpressions(array $expressions): void {
        $this->expressions = $expressions;
    }

    /**
     * @return string
     */
    public function getSQLTransformationProcessorKey(): string {
        return "pivot";
    }

}