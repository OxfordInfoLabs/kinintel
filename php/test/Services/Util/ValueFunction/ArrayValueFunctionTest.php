<?php


namespace Kinintel\Test\Services\Util\ValueFunction;

use Kinintel\Services\Util\ValueFunction\ArrayValueFunction;


include_once "autoloader.php";

class ArrayValueFunctionTest extends \PHPUnit\Framework\TestCase {

    public function testCanGetMemberValueArrayFromArrayOfObjects() {

        $function = new ArrayValueFunction();
        $this->assertTrue($function->doesFunctionApply("memberValues"));


        $array = [
            [
                "id" => 1,
                "name" => "Mark"
            ],
            [
                "id" => 2,
                "name" => "Mary"
            ],
            [
                "id" => 3,
                "name" => "Paul"
            ]
        ];

        $this->assertEquals([1, 2, 3], $function->applyFunction("memberValues id", $array, null));
        $this->assertEquals(["Mark", "Mary", "Paul"], $function->applyFunction("memberValues name", $array, null));


    }

    public function testCanJoinArrayValuesUsingDelimiter() {

        $function = new ArrayValueFunction();
        $this->assertTrue($function->doesFunctionApply("join"));


        $array = ["Mark", "James", "Mary"];
        $this->assertEquals("Mark,James,Mary", $function->applyFunction("join ,", $array, null));
        $this->assertEquals("Mark;James;Mary", $function->applyFunction("join ;", $array, null));

    }

}