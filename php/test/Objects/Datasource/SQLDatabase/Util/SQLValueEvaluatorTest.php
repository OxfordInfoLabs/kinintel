<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\Util;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLValueEvaluator;
use function PHPUnit\Framework\assertEquals;

include_once "autoloader.php";

class SQLValueEvaluatorTest extends \PHPUnit\Framework\TestCase {


    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    public function setUp(): void {
        $this->databaseConnection = new SQLite3DatabaseConnection();
    }


    public function testPlainStringAndIntegerValuesAreExtractedWithoutChange() {

        // Straight string
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("Hello World", [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals(["Hello World"], $parameters);

        // Straight number
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue(11, [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals([11], $parameters);

        // String with brackets in
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("Hello (Test Code)", [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals(["Hello (Test Code)"], $parameters);

        // String with number in brackets
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("Hello (1e)", [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals(["Hello (1e)"], $parameters);


        // String with number in brackets
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("Hello (1)", [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals(["Hello (1)"], $parameters);



        // String with brackets in
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("In (My)(Bed)", [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals(["In (My)(Bed)"], $parameters);

        // Partial brackets
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("Hello(T*", [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals(["Hello(T*"], $parameters);

        // Null
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue(null, [], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertEquals([null], $parameters);


    }


    public function testMathsExpressionsInBracketsAreRetained() {

        // String with brackets in
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("2 * (3 * 6)", [], null, $parameters);
        $this->assertEquals("? * (? * ?)", $value);
        $this->assertEquals([2, 3, 6], $parameters);

    }

    public function testColumnNamesInDoubleSquareBracketsGetEvaluatedAndPrefixedWithTablePrefixIfSupplied() {

        // Unprefixed
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("SUM([[total]]) + [[metric]]", [], null, $parameters);
        $this->assertEquals("SUM(\"total\") + \"metric\"", $value);
        $this->assertEquals([], $parameters);

        // Prefixed
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("SUM([[total]]) + [[metric]]", [], "TT", $parameters);
        $this->assertEquals("SUM(TT.\"total\") + TT.\"metric\"", $value);
        $this->assertEquals([], $parameters);


    }


    public function testParametersInDoubleBracesGetEvaluatedAsBoundParameters() {

        // Numbers
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{first}} + {{second}}", ["first" => 55, "second" => 23], null, $parameters);
        $this->assertEquals("? + ?", $value);
        $this->assertSame([55, 23], $parameters);


        // Array one
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{array}}", ["array" => [1, 2, 3, 4, 5, 6, 7]], null, $parameters);
        $this->assertEquals("?,?,?,?,?,?,?", $value);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $parameters);

        // Strings
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{test}}", ["test" => "Hello"], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertSame(["Hello"], $parameters);

        // Apostrophe words
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{test}}", ["test" => "Who's outside it's cold"], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertSame(["Who's outside it's cold"], $parameters);

        // Complex strings with injection risks are correctly encoded
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{test}}", ["test" => "9999999999' or '1'='"], null, $parameters);
        $this->assertEquals("?", $value);
        $this->assertSame(["9999999999' or '1'='"], $parameters);

        // We can evaluate simple expressions
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{test}}_DAYS_AGO", ["test" => "1"], null, $parameters);
        $this->assertEquals("?", $value);
        $year7DaysAgo = substr($parameters[0], 0, 4);
        $year = date_create()->format("Y");
        $this->assertTrue($year === $year7DaysAgo || $year == ((int)$year7DaysAgo + 1));

    }


    public function testCanUseDaysAgoForHistoricTimePeriodsInSQLFilterValuesAndBoundParameterCreatedForQuery() {

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("1_DAYS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P1D"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("7_DAYS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P7D"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("5_HOURS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("PT5H"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("4_MINUTES_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("PT4M"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("25_SECONDS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("PT25S"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("1_MONTHS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P1M"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("6_MONTHS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P6M"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("1_YEARS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P1Y"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

    }


    public function testTimePeriodStringsPassedAsParametersAreEvaluatedCorrectly() {
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{timePeriod}}", ["timePeriod" => "1_DAYS_AGO"], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P1D"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $parameters = [];
        $value = $evaluator->evaluateFilterValue("'{{timePeriod}}'", ["timePeriod" => "2_DAYS_AGO"], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P2D"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);
    }

    public function testCanProvideParameterisedValuesInAQuotedString(){
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("CONCAT( 'The big {{red}} ', 'fish')", ["red" => "scarlet"], null, $parameters);
        $this->assertEquals(["The big scarlet ", "fish"], $parameters);
        $this->assertEquals("CONCAT( ?, ?)", $value);

        $parameters = [];
        $value = $evaluator->evaluateFilterValue("CONCAT('It was ',{{furniture}},' shaped', '.')",  ["furniture" => "chair"], null, $parameters);
        $this->assertEquals(["It was ", "chair", " shaped", "."], $parameters);
        $this->assertEquals("CONCAT(?,?,?, ?)", $value);
    }

    public function testRegexIsParsedCorrectly(){
        $evaluator = new SQLValueEvaluator($this->databaseConnection);
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("'like'", outputParameters: $parameters);
        $this->assertEquals(["'like'"], $parameters);
        $this->assertEquals("?", $value);

        $parameters = [];
        $value = $evaluator->evaluateFilterValue("[2-9]|[12]\d|3[0-6]", outputParameters: $parameters);
        $this->assertEquals(["[2-9]|[12]\d|3[0-6]"], $parameters);
        $this->assertEquals("?", $value);

        $parameters = [];
        $value = $evaluator->evaluateFilterValue("w{3}\.google\.com", outputParameters: $parameters);
        assertEquals(1, preg_match("/w{3}\.google\.com/", "www.google.com"));
        $this->assertEquals(["w{3}\.google\.com"], $parameters);
        $this->assertEquals("?", $value);

    }

    //TODO
//    public function testSubstitution(){
//        $evaluator = new SQLValueEvaluator($this->databaseConnection);
//        $params = [];
//        $value = $evaluator->evaluateFilterValue("LEVENSHTEIN('{{word}}a', '{{word}}')", ["word" => "w"], null, $params);
//        $this->assertEquals(["wa", "w"], $params);
//    }
}