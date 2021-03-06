<?php


namespace Kinintel\Test\Services\Util;


use Kinintel\Services\Util\ValueFunctionEvaluator;
use Kinintel\TestBase;

include_once "autoloader.php";

class ValueFunctionEvaluatorTest extends TestBase {


    /**
     * @var ValueFunctionEvaluator
     */
    private $evaluator;

    public function setUp(): void {
        $this->evaluator = new ValueFunctionEvaluator();
    }

    public function testCanResolveFieldValueForBuiltInEvaluators() {

        $this->assertEquals("cde", $this->evaluator->evaluateValueFunction("/.*(cde).*/", "abcdefg", ["test" => "abcdefg"]));
        $this->assertEquals("March", $this->evaluator->evaluateValueFunction("monthName", "2020-03-02", ["test" => "abcdefg"]));
    }

    public function testIfNoEvaluatorResolvedValueReturnedIntact() {
        $this->assertEquals("Bingo", $this->evaluator->evaluateValueFunction("test", "Bingo", ["test" => "abcdefg"]));
    }


    public function testCanResolveAllFieldValuesForPassedStringWithDelimiters() {

        $this->assertEquals("ell March Bingo", $this->evaluator->evaluateString("[[string | /.*(ell).*/]] [[date | monthName]] [[plain]]",
            ["string" => "Hello", "date" => "2020-03-02", "plain" => "Bingo"]));


        $this->assertEquals("ell March Bingo", $this->evaluator->evaluateString("{{string | /.*(ell).*/}} {{date | monthName}} {{plain}}",
            ["string" => "Hello", "date" => "2020-03-02", "plain" => "Bingo"], ["{{", "}}"]));

    }


    public function testCanResolveSpecialExpressionsInDelimiters() {

        $this->assertEquals(date("Y-m-d H:i:s"), $this->evaluator->evaluateString("[[NOW]]"));
        $this->assertEquals(date("d/m/Y"), $this->evaluator->evaluateString("[[NOW | dateConvert 'Y-m-d H:i:s' 'd/m/Y']]"));

        $now = new \DateTime();
        $now->sub(new \DateInterval("P2D"));
        $this->assertEquals($now->format("d/m/Y"), $this->evaluator->evaluateString("[[2_DAYS_AGO | dateConvert 'Y-m-d H:i:s' 'd/m/Y']]"));

        $now = new \DateTime();
        $now->sub(new \DateInterval("PT3H"));
        $this->assertEquals($now->format("d/m/Y H:i"), $this->evaluator->evaluateString("[[3_HOURS_AGO | dateConvert 'Y-m-d H:i:s' 'd/m/Y H:i']]"));
    }


}