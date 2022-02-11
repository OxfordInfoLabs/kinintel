<?php


namespace Kinintel\Test\ValueObjects\Dataset\FieldValueFunction;


use Kinintel\ValueObjects\Dataset\FieldValueFunction\ConversionFieldValueFunction;
use Kinintel\ValueObjects\Dataset\FieldValueFunction\DateFormatFieldValueFunction;

include_once "autoloader.php";

class ConversionFieldValueFunctionTest extends \PHPUnit\Framework\TestCase {

    public function testFunctionIsResolvedForKnownFunctionNames() {

        $function = new ConversionFieldValueFunction();
        $this->assertFalse($function->doesFunctionApply("imaginary"));
        $this->assertFalse($function->doesFunctionApply("test"));

        $this->assertTrue($function->doesFunctionApply("toJSON"));
    }


    public function testCanConvertToJSONFormat() {

        $function = new ConversionFieldValueFunction();
        $this->assertEquals(json_encode([1, 2, 3]), $function->applyFunction("toJSON", [1, 2, 3], null));
        $this->assertEquals(json_encode("Mark"), $function->applyFunction("toJSON", "Mark", null));

    }
}