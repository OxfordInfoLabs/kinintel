<?php


namespace Kinintel\Test\Services\Util\ValueFunction;

use Kinintel\Services\Util\ValueFunction\RegExValueFunction;

include_once "autoloader.php";

class RegExValueFunctionTest extends \PHPUnit\Framework\TestCase {

    public function testRegExExpressionsStartingAndEndingWithDelimitersAreAcceptedAndProcessedUsingFullOrCaptureExpression() {

        $function = new RegExValueFunction();
        $this->assertFalse($function->doesFunctionApply("test"));
        $this->assertFalse($function->doesFunctionApply("regex()"));
        $this->assertFalse($function->doesFunctionApply("/onesided"));
        $this->assertTrue($function->doesFunctionApply("/valid/"));


        $this->assertEquals("cde", $function->applyFunction("/c.*?e/", "abcdefg", []));
        $this->assertEquals("01", $function->applyFunction("/^.{3}(.{2})/", "03/01/2022", []));
    }


}