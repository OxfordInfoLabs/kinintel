<?php


namespace Kinintel\ValueObjects\Parameter;

include_once "autoloader.php";

class ParameterTest extends \PHPUnit\Framework\TestCase {

    public function testCanValidateParameterValuesBasedOnTypes() {

        // String values very tolerant
        $parameter = new Parameter("test", "Test");
        $this->assertTrue($parameter->validateParameterValue("Hello world"));
        $this->assertTrue($parameter->validateParameterValue(12345));
        $this->assertTrue($parameter->validateParameterValue(true));

        // Numeric types must be numbers
        $parameter = new Parameter("test", "Test", Parameter::TYPE_NUMERIC);
        $this->assertFalse($parameter->validateParameterValue("Hello world"));
        $this->assertTrue($parameter->validateParameterValue(12345));
        $this->assertFalse($parameter->validateParameterValue(true));

        // Booleans can either be true/false or 0/1
        $parameter = new Parameter("test", "Test", Parameter::TYPE_BOOLEAN);
        $this->assertFalse($parameter->validateParameterValue("Hello world"));
        $this->assertFalse($parameter->validateParameterValue(12345));
        $this->assertTrue($parameter->validateParameterValue(true));
        $this->assertTrue($parameter->validateParameterValue(false));
        $this->assertTrue($parameter->validateParameterValue(0));
        $this->assertTrue($parameter->validateParameterValue(1));

        // Date formats
        $parameter = new Parameter("test", "Test", Parameter::TYPE_DATE);
        $this->assertFalse($parameter->validateParameterValue("Hello world"));
        $this->assertFalse($parameter->validateParameterValue(12345));
        $this->assertFalse($parameter->validateParameterValue(true));
        $this->assertFalse($parameter->validateParameterValue("01/01/2020"));
        $this->assertTrue($parameter->validateParameterValue("2020-01-01"));

        // Date time formats
        $parameter = new Parameter("test", "Test", Parameter::TYPE_DATE_TIME);
        $this->assertFalse($parameter->validateParameterValue("Hello world"));
        $this->assertFalse($parameter->validateParameterValue(12345));
        $this->assertFalse($parameter->validateParameterValue(true));
        $this->assertFalse($parameter->validateParameterValue("01/01/2020 10:00:22"));
        $this->assertFalse($parameter->validateParameterValue("2020-01-01 10:00"));
        $this->assertTrue($parameter->validateParameterValue("2020-01-01 10:00:05"));


    }


}