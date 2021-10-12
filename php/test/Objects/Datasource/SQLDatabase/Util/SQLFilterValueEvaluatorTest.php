<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\Util;

use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterValueEvaluator;

include_once "autoloader.php";

class SQLFilterValueEvaluatorTest extends \PHPUnit\Framework\TestCase {


    public function testTableNamesInDoubleSquareBracketsGetEvaluatedAndPrefixedWithTablePrefixIfSupplied() {

        // Unprefixed
        $evaluator = new SQLFilterValueEvaluator();
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("SUM([[total]]) + [[metric]]", [], null, $parameters);
        $this->assertEquals("SUM(total) + metric", $value);
        $this->assertEquals([], $parameters);

        // Prefixed
        $evaluator = new SQLFilterValueEvaluator();
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("SUM([[total]]) + [[metric]]", [], "TT", $parameters);
        $this->assertEquals("SUM(TT.total) + TT.metric", $value);
        $this->assertEquals([], $parameters);


    }


    public function testParametersInDoubleBracesGetEvaluatedAsBoundParameters() {

        $evaluator = new SQLFilterValueEvaluator();
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{first}} + {{second}}", ["first" => 55, "second" => 23], null, $parameters);
        $this->assertEquals("? + ?", $value);
        $this->assertEquals([55, 23], $parameters);


        // Array one
        $evaluator = new SQLFilterValueEvaluator();
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("{{array}}", ["array" => [1, 2, 3, 4, 5, 6, 7]], null, $parameters);
        $this->assertEquals("?,?,?,?,?,?,?", $value);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $parameters);


    }


    public function testCanUseDaysAgoForHistoricTimePeriodsInSQLFilterValuesAndBoundParameterCreatedForQuery() {

        $evaluator = new SQLFilterValueEvaluator();
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("1_DAYS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P1D"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLFilterValueEvaluator();
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("7_DAYS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("P7D"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);

        $evaluator = new SQLFilterValueEvaluator();
        $parameters = [];
        $value = $evaluator->evaluateFilterValue("5_HOURS_AGO", [], null, $parameters);
        $this->assertEquals([(new \DateTime())->sub(new \DateInterval("PT5H"))->format("Y-m-d H:i:s")], $parameters);
        $this->assertEquals("?", $value);


    }


}