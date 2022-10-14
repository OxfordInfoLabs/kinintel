<?php

namespace Kinintel\Test\Services\Util\ValueFunction;

use Kinintel\Services\Util\ValueFunction\StringValueFunction;

include_once "autoloader.php";

class StringValueFunctionTest extends \PHPUnit\Framework\TestCase{

    public function testCanReturnSubstringGivenIndexes() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("substring"));

        $string = "This is a test string!";
        $this->assertEquals("test string!", $function->applyFunction("substring 10",$string,null));
        $this->assertEquals(" is a", $function->applyFunction("substring 4 5",$string,null));
        $this->assertEquals("Thi", $function->applyFunction("substring 0 3",$string,null));
    }

}