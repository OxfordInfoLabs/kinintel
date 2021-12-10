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
        $this->assertEquals("Buffalo", $function->applyFunction("ifNot text", "", ["text" => "Buffalo"]));

        // Check nested one
        $this->assertEquals("Buffalo", $function->applyFunction("ifNot text.nested", "", ["text" => ["nested" => "Buffalo"]]));


    }

}