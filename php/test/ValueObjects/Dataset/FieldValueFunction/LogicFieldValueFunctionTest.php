<?php


namespace Kinintel\Test\ValueObjects\Dataset\FieldValueFunction;

use Kinintel\ValueObjects\Dataset\FieldValueFunction\LogicFieldValueFunction;

include_once "autoloader.php";

class LogicFieldValueFunctionTest extends \PHPUnit\Framework\TestCase {


    public function testIfNotFunctionEvaluatesCorrectly() {

        $function = new LogicFieldValueFunction();
        $this->assertTrue($function->doesFunctionApply("ifNot"));

        // Check pass through if text set
        $this->assertEquals("hello", $function->applyFunction("ifNot text", "hello", ["text" => "Buffalo"]));

        // Check reset of no text set
        $this->assertEquals("Buffalo", $function->applyFunction("ifNot text 'hello' new", "", ["text" => "Buffalo"]));

        // Check nested one
        $this->assertEquals("Buffalo", $function->applyFunction("ifNot text.nested", "", ["text" => ["nested" => "Buffalo"]]));


    }


    public function testAddAndSubtractFunctionsEvaluateCorrectlyForMemberAndLiteralValues() {

        $function = new LogicFieldValueFunction();
        $this->assertEquals(40, $function->applyFunction("add 10", 30, []));
        $this->assertEquals(40, $function->applyFunction("add hello", 20, ["hello" => 20]));

        $this->assertEquals(20, $function->applyFunction("subtract 10", 30, []));
        $this->assertEquals(0, $function->applyFunction("subtract hello", 20, ["hello" => 20]));


    }

}