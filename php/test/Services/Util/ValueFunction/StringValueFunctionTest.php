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

    public function testCanConcatenateStrings() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("concat"));

        $string1 = "First";
        $string2 = "Second";
        $string3 = "Third";

        $this->assertEquals("FirstSecond", $function->applyFunction("concat '$string2'", $string1, null));
        $this->assertEquals("FirstThird", $function->applyFunction("concat '$string3'", $string1, null));
        $this->assertEquals("SecondFirst", $function->applyFunction("concat '$string1'", $string2, null));
        $this->assertEquals("FirstSecondThird", $function->applyFunction("concat '$string2' '$string3'", $string1, null));
    }

    public function testCanConvertToUTF8OrNullIfNot() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("toUTF8"));

        $string1 = "hello world";
        $string2 = "🖤.eth";
        $string3 = "Hello\xF0\x9F\x92\xB8\xF0\x9F.eth";

        $this->assertEquals($string1, $function->applyFunction("toUTF8", $string1, null));
        $this->assertNull($function->applyFunction("toUTF8", $string2, null));
        $this->assertNull($function->applyFunction("toUTF8", $string3, null));

    }

    public function testCanTrimStrings() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("trim"));

        $string1 = "Test...";
        $string2 = "$%test//";

        $this->assertEquals("Test", $function->applyFunction("trim '.'", $string1, null));
        $this->assertEquals("est...", $function->applyFunction("trim 'T'", $string1, null));
        $this->assertEquals("est", $function->applyFunction("trim 'T.'", $string1, null));
        $this->assertEquals("test", $function->applyFunction("trim '/$%'", $string2, null));
        $this->assertEquals($string2, $function->applyFunction("trim s", $string2, null));

    }
}
