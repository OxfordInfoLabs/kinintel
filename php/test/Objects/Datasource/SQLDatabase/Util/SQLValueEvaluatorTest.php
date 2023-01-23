<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\Util;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLValueEvaluator;

include_once "autoloader.php";

class SQLValueEvaluatorTest extends \PHPUnit\Framework\TestCase {


    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    public function setUp(): void {
        $this->databaseConnection = new SQLite3DatabaseConnection();
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
    }


}