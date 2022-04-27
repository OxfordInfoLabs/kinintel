<?php


namespace Kinintel\Test\Services\Util\ValueFunction;


use Kinintel\Services\Util\ValueFunction\ConversionValueFunction;

include_once "autoloader.php";

class ConversionValueFunctionTest extends \PHPUnit\Framework\TestCase {

    public function testFunctionIsResolvedForKnownFunctionNames() {

        $function = new ConversionValueFunction();
        $this->assertFalse($function->doesFunctionApply("imaginary"));
        $this->assertFalse($function->doesFunctionApply("test"));

        $this->assertTrue($function->doesFunctionApply("toJSON"));
        $this->assertTrue($function->doesFunctionApply("toNumber"));
    }


    public function testCanConvertToJSONFormat() {

        $function = new ConversionValueFunction();
        $this->assertEquals(json_encode([1, 2, 3]), $function->applyFunction("toJSON", [1, 2, 3], null));
        $this->assertEquals(json_encode("Mark"), $function->applyFunction("toJSON", "Mark", null));

    }

    public function testCanConvertToNumber() {

        $function = new ConversionValueFunction();
        $this->assertEquals(25, $function->applyFunction("toNumber", 25, null));
        $this->assertEquals(2500, $function->applyFunction("toNumber", "2,500", null));
        $this->assertNull($function->applyFunction("toNumber", "Bingo", null));
        $this->assertEquals(0, $function->applyFunction("toNumber 0", "HELLO", null));
        $this->assertEquals(5, $function->applyFunction("toNumber 5", null, null));
        $this->assertEquals(5, $function->applyFunction("toNumber 5", "", null));
    }

}