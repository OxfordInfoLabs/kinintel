<?php


namespace Kinintel\Test\Services\Util\ValueFunction;

use Kinintel\Services\Util\ValueFunction\LogicValueFunction;

include_once "autoloader.php";

class LogicValueFunctionTest extends \PHPUnit\Framework\TestCase {


    public function testIfNotFunctionEvaluatesCorrectly() {

        $function = new LogicValueFunction();
        $this->assertTrue($function->doesFunctionApply("ifNot"));

        // Check pass through if text set
        $this->assertEquals("hello", $function->applyFunction("ifNot text", "hello", ["text" => "Buffalo"]));

        // Check reset of no text set
        $this->assertEquals("Buffalo", $function->applyFunction("ifNot text 'hello' new", "", ["text" => "Buffalo"]));

        // Check nested one
        $this->assertEquals("Buffalo", $function->applyFunction("ifNot text.nested", "", ["text" => ["nested" => "Buffalo"]]));


    }


    public function testAddAndSubtractFunctionsEvaluateCorrectlyForMemberAndLiteralValues() {

        $function = new LogicValueFunction();
        $this->assertEquals(40, $function->applyFunction("add 10", 30, []));
        $this->assertEquals(40, $function->applyFunction("add hello", 20, ["hello" => 20]));

        $this->assertEquals(20, $function->applyFunction("subtract 10", 30, []));
        $this->assertEquals(0, $function->applyFunction("subtract hello", 20, ["hello" => 20]));


    }

    public function testTernaryExpressionsAreEvaluatedCorrectly(){

        $function = new LogicValueFunction();
        $this->assertEquals("Yes", $function->applyFunction("ternary 'Yes' 'No'", true, null));
        $this->assertEquals("No", $function->applyFunction("ternary 'Yes' 'No'", false, null));

        $function = new LogicValueFunction();
        $this->assertEquals("Bong", $function->applyFunction("ternary 'Bong' 'Bung'", 1, null));
        $this->assertEquals("Bung", $function->applyFunction("ternary 'Bong' 'Bung'", 0, null));

    }

}