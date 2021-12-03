<?php


namespace Kinintel\Test\ValueObjects\Dataset\FieldValueFunction;

use Kinintel\ValueObjects\Dataset\FieldValueFunction\RegExFieldValueFunction;

include_once "autoloader.php";

class RegExFieldValueFunctionTest extends \PHPUnit\Framework\TestCase {

    public function testRegExExpressionsStartingAndEndingWithDelimitersAreAcceptedAndProcessedUsingFullOrCaptureExpression() {

        $function = new RegExFieldValueFunction();
        $this->assertFalse($function->doesFunctionApply("test"));
        $this->assertFalse($function->doesFunctionApply("regex()"));
        $this->assertFalse($function->doesFunctionApply("/onesided"));
        $this->assertTrue($function->doesFunctionApply("/valid/"));


        $this->assertEquals("cde", $function->applyFunction("/c.*?e/", "abcdefg"));
        $this->assertEquals("01", $function->applyFunction("/^.{3}(.{2})/", "03/01/2022"));
    }


}