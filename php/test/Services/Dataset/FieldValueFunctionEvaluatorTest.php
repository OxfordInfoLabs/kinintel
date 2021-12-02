<?php


namespace Kinintel\Test\Services\Dataset;


use Kinintel\Services\Dataset\FieldValueFunctionEvaluator;
use Kinintel\TestBase;

class FieldValueFunctionEvaluatorTest extends TestBase {


    /**
     * @var FieldValueFunctionEvaluator
     */
    private $evaluator;

    public function setUp(): void {
        $this->evaluator = new FieldValueFunctionEvaluator();
    }

    public function testCanResolveFieldValueForBuiltInEvaluators() {

        $this->assertEquals("cde", $this->evaluator->evaluateFieldValueFunction("/.*(cde).*/", "abcdefg"));
        $this->assertEquals("March", $this->evaluator->evaluateFieldValueFunction("monthName", "2020-03-02"));
    }

    public function testIfNoEvaluatorResolvedValueReturnedIntact() {
        $this->assertEquals("Bingo", $this->evaluator->evaluateFieldValueFunction("test", "Bingo"));
    }

}