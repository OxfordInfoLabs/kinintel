<?php


namespace Kinintel\Test\Services\Util;

use Kinintel\Services\Util\SQLClauseSanitiser;
use Kinintel\TestBase;

include_once "autoloader.php";

class SQLClauseSanitiserTest extends TestBase {

    /**
     * @var SQLClauseSanitiser
     */
    private $sqlClauseSanitiser;


    /**
     * Set up function
     */
    public function setUp(): void {
        $this->sqlClauseSanitiser = new SQLClauseSanitiser();
    }

    public function testSimpleNumericAndStringExpressionsAreReturnedParameterised() {

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("'HELLO'", $params);
        $this->assertEquals("?", $sql);
        $this->assertSame(["HELLO"], $params);

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("33", $params);
        $this->assertEquals("?", $sql);
        $this->assertSame([33], $params);

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("33.334", $params);
        $this->assertEquals("?", $sql);
        $this->assertSame([33.334], $params);

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("33.334 || 'Hello 123'", $params);
        $this->assertEquals("? || ?", $sql);
        $this->assertSame([33.334, "Hello 123"], $params);

    }

    public function testSimpleExpressionsInSquareBracketsAreLeftIntact() {
        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("[[Param1]] || [[Param2]]", $params);
        $this->assertEquals("[[Param1]] || [[Param2]]", $sql);
        $this->assertEquals([], $params);

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("123 || [[Param1]] || [[param_2]] || [[param-3]]", $params);
        $this->assertEquals("? || [[Param1]] || [[param_2]] || [[param-3]]", $sql);
        $this->assertEquals([123], $params);

        // Complex expressions removed in []
        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("123 || [[Complex * Two]] || [[Bing One Two]]", $params);
        $this->assertEquals("? ||  || ", $sql);
        $this->assertEquals([123], $params);

    }


    public function testExistingLiteralValuesAreLeftIntactAndParametersInsertedInRightPlace() {

        $params = [1, 2];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("66 + ? + 77 + ? + 88", $params);
        $this->assertEquals("? + ? + ? + ? + ?", $sql);
        $this->assertEquals([66, 1, 77, 2, 88], $params);

    }


    public function testWhitelistedSymbolsAreLeftIntact() {

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("3*7+9/2-4 % 77", $params);
        $this->assertEquals("?*?+?/?-? % ?", $sql);
        $this->assertEquals([3, 7, 9, 2, 4, 77], $params);

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("CASE WHEN [[Param1]] > 2 THEN 'Bob' WHEN [[Param1]] < 10 THEN 'Mary' WHEN [[Param1]] = 15 THEN 'James' WHEN [[Param2]] != 20 THEN 'JIM' END", $params);
        $this->assertEquals("CASE WHEN [[Param1]] > ? THEN ? WHEN [[Param1]] < ? THEN ? WHEN [[Param1]] = ? THEN ? WHEN [[Param2]] != ? THEN ? END", $sql);
        $this->assertEquals([2, "Bob", 10, "Mary", 15, "James", 20, "JIM"], $params);


        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("(2 * 4) + (3 - 2)", $params);
        $this->assertEquals("(? * ?) + (? - ?)", $sql);
        $this->assertEquals([2, 4, 3, 2], $params);


    }


    public function testWhitelistedKeywordsAndFunctionNamesAreLeftIntact() {

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("CASE WHEN 1 BETWEEN 3 AND 4 THEN 'HEY' END", $params);
        $this->assertEquals("CASE WHEN ? BETWEEN ? AND ? THEN ? END", $sql);
        $this->assertEquals([1, 3, 4, 'HEY'], $params);


        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("MIN(2,4) + MAX(3,6) || TRIM('HELLO')", $params);
        $this->assertEquals("MIN(?,?) + MAX(?,?) || TRIM(?)", $sql);
        $this->assertEquals([2, 4, 3, 6, "HELLO"], $params);

        // lower case ok as well
        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("min(2,4) + max(3,6) || trim('HELLO')", $params);
        $this->assertEquals("min(?,?) + max(?,?) || trim(?)", $sql);
        $this->assertEquals([2, 4, 3, 6, "HELLO"], $params);


    }


    public function testNonWhitelistedSymbolsAndFunctionsAreRemovedFromString() {

        $params = [];
        $sql = $this->sqlClauseSanitiser->sanitiseSQL("CASE WHEN 1 UNKNOWN AVG(3) AND 4; THEN INSERT INTO my_table 'HEY' END", $params);
        $this->assertEquals("CASE WHEN ?  AVG(?) AND ? THEN    ? END", $sql);
        $this->assertEquals([1, 3, 4, "HEY"], $params);

    }


}