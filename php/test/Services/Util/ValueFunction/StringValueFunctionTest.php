<?php

namespace Kinintel\Test\Services\Util\ValueFunction;

use Kinintel\Services\Util\ValueFunction\StringValueFunction;

include_once "autoloader.php";

class StringValueFunctionTest extends \PHPUnit\Framework\TestCase {

    public function testCanReturnSubstringGivenIndexes() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("substring"));

        $string = "This is a test string!";
        $this->assertEquals("test string!", $function->applyFunction("substring 10", $string, null));
        $this->assertEquals(" is a", $function->applyFunction("substring 4 5", $string, null));
        $this->assertEquals("Thi", $function->applyFunction("substring 0 3", $string, null));
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

    public function testCanExplodeStringToArray() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("explode"));

        $string1 = "1,2,3,4";
        $string2 = "first second third";

        $this->assertEquals(["1", "2", "3", "4"], $function->applyFunction("explode ','", $string1, null));
        $this->assertEquals(["first", "second", "third"], $function->applyFunction("explode ' '", $string2, null));
        $this->assertEquals(["1,2,3,4"], $function->applyFunction("explode ' '", $string1, null));
    }

    public function testCanDoRegexReplace() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("replace"));

        // Non reg-ex replace
        $this->assertEquals("Hello dave", $function->applyFunction("replace 'World' 'dave'", "Hello World", null));
        $this->assertEquals("Hello dave amongst many daves", $function->applyFunction("replace 'World' 'dave'", "Hello World amongst many Worlds", null));

        // Reg-ex replace
        $this->assertEquals("This is Bingo indeed Bingo", $function->applyFunction("replace '/[0-9]+/' 'Bingo'", "This is 12345 indeed 45678", null));
        $this->assertEquals(" truncated", $function->applyFunction("replace '/^[a-zA-Z]+/' ''", "Iam truncated", null));

    }

    public function testCanFindStringInString() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("contains"));

        $this->assertEquals(true, $function->applyFunction("contains 'l'", "hello", null));
        $this->assertEquals(true, $function->applyFunction("contains 'eve'", "Steve", null));
        $this->assertEquals(true, $function->applyFunction("contains ':'", "2001:23:43::56/32", null));
        $this->assertEquals(true, $function->applyFunction("contains '.'", "192.168.0.0/24", null));

        $this->assertEquals(false, $function->applyFunction("contains 'w'", "test", null));
        $this->assertEquals(false, $function->applyFunction("contains '4'", "123", null));

    }

    public function testCanConvertStringToUpper() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("toUpper"));

        $this->assertEquals("TEST", $function->applyFunction("toUpper", "test", null));
        $this->assertEquals("EXAMPLE STRING", $function->applyFunction("toUpper", "example string", null));
        $this->assertEquals("I'M A SHOUTY MAN!", $function->applyFunction("toUpper", "I'm a shouty man!", null));
        $this->assertEquals("ARRGHHHH", $function->applyFunction("toUpper", "arrghhhh", null));
        $this->assertEquals("OH NO!", $function->applyFunction("toUpper", "oh no!", null));

    }

    public function testCanConvertStringToLower() {
        $function = new StringValueFunction();
        $this->assertTrue($function->doesFunctionApply("toLower"));

        $this->assertEquals("test", $function->applyFunction("toLower", "TEST", null));
        $this->assertEquals("shhhhhh", $function->applyFunction("toLower", "SHhHHhh", null));
        $this->assertEquals("it's so quiet....", $function->applyFunction("toLower", "IT'S SO quiet....", null));
        $this->assertEquals("hello!", $function->applyFunction("toLower", "HELLO!", null));
        $this->assertEquals("low low low", $function->applyFunction("toLower", "LOW low LOW", null));

    }
}
