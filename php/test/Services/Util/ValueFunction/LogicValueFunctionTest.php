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
        $this->assertEquals(-4.23, $function->applyFunction("add float", -5, ["float" => 0.77]));

        $this->assertEquals(20, $function->applyFunction("subtract 10", 30, []));
        $this->assertEquals(0, $function->applyFunction("subtract hello", 20, ["hello" => 20]));
        $this->assertEquals(1.8, $function->applyFunction("subtract float", 1.4, ["float" => -0.4]));

    }

    public function testMultiplyAndDivideFunctionsEvaluateCorrectlyForMemberAndLiteralValues() {
        $function = new LogicValueFunction();
        $this->assertEquals(143, $function->applyFunction("multiply 11", 13, []));
        $this->assertEquals(-24, $function->applyFunction("multiply -6", 4, []));
        $this->assertEquals(3, $function->applyFunction("multiply hello", 0.5, ["hello" => 6]));
        $this->assertEquals(-3.2, $function->applyFunction("multiply float", 2, ["float" => -1.6]));

        $this->assertEquals(5, $function->applyFunction("divide 2", 10, []));
        $this->assertEquals(2.5, $function->applyFunction("divide 4", 10, []));
        $this->assertEquals(-11/13, $function->applyFunction("divide hello", 11, ["hello" => -13]));
        $this->assertEquals(2, $function->applyFunction("divide float", 1.4, ["float" => 0.7]));
    }

    public function testModuloAndFloorFunctionsEvaluateCorrectlyForMemberAndLiteralValues() {
        $function = new LogicValueFunction();
        $this->assertEquals(3, $function->applyFunction("modulo 4", 11, []));
        $this->assertEquals(2, $function->applyFunction("modulo 5.5", 12, []));
        $this->assertEquals(2, $function->applyFunction("modulo hello", 12, ["hello" => 10]));
        $this->assertEquals(0, $function->applyFunction("modulo goodbye", 4.5, ["goodbye" => 1.4]));

        $this->assertEquals(11, $function->applyFunction("floor", 11.6, []));
        $this->assertEquals(-12, $function->applyFunction("floor", -11.6, []));
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