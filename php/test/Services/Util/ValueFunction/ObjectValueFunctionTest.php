<?php

namespace Kinintel\Test\Services\Util\ValueFunction;

use Kinintel\Services\Util\ValueFunction\ObjectValueFunction;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class ObjectValueFunctionTest extends TestCase {

    public function testCanReturnSingleMember() {
        $function = new ObjectValueFunction();
        $this->assertTrue($function->doesFunctionApply("member"));

        $testArray = ["item1" => [1,2,3], "item2" => [4,5,6]];

        $this->assertEquals([1,2,3], $function->applyFunction("member 'item1'", $testArray, null));
        $this->assertEquals([4,5,6], $function->applyFunction("member 'item2'", $testArray, null));

    }

}