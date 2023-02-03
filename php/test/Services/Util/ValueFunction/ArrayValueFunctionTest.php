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

    public function testCanSliceArrayBetweenIndexes() {
        $function = new ArrayValueFunction();
        $this->assertTrue($function->doesFunctionApply("slice"));

        $array = ["Mark", "James", "Mary"];
        $this->assertEquals(["James", "Mary"], $function->applyFunction("slice 1", $array, null));
        $this->assertEquals(["James"], $function->applyFunction("slice 1 1", $array, null));
        $this->assertEquals(["Mary"], $function->applyFunction("slice 2 1", $array, null));
        $this->assertEquals(["Mark", "James"], $function->applyFunction("slice 0 2", $array, null));

    }

    public function testCanPickItemFromArray() {
        $function = new ArrayValueFunction();
        $this->assertTrue($function->doesFunctionApply("item"));

        $array = ["Steve", 34, []];
        $this->assertEquals("Steve", $function->applyFunction("item 0", $array, null));
        $this->assertEquals(34, $function->applyFunction("item 1", $array, null));
        $this->assertEquals([], $function->applyFunction("item 2", $array, null));
        $this->assertEquals(null, $function->applyFunction("item 3", $array, null));

    }

    public function testCanPopItemFromArray() {
        $function = new ArrayValueFunction();
        $this->assertTrue($function->doesFunctionApply("pop"));

        $array1 = [1,2,3,4];
        $array2 = ["uno", "dos", "tres"];
        $array3 = ["single"];
        $this->assertEquals(4, $function->applyFunction("pop", $array1, null));
        $this->assertEquals("tres", $function->applyFunction("pop", $array2, null));
        $this->assertEquals("single", $function->applyFunction("shift", $array3, null));

    }

    public function testCanShiftItemFromArray() {
        $function = new ArrayValueFunction();
        $this->assertTrue($function->doesFunctionApply("shift"));

        $array1 = [1,2,3,4];
        $array2 = ["uno", "dos", "tres"];
        $array3 = ["single"];
        $this->assertEquals(1, $function->applyFunction("shift", $array1, null));
        $this->assertEquals("uno", $function->applyFunction("shift", $array2, null));
        $this->assertEquals("single", $function->applyFunction("shift", $array3, null));

    }
}