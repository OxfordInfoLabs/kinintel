<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\Util;

use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterValueEvaluator;

include_once "autoloader.php";

class SQLFilterValueEvaluatorTest extends \PHPUnit\Framework\TestCase {

    public function testCanUseDaysAgoForHistoricTimePeriodsInSQLFilterValues() {

        $evaluator = new SQLFilterValueEvaluator();
        $value = $evaluator->evaluateFilterValue("1_DAYS_AGO");
        $this->assertEquals((new \DateTime())->sub(new \DateInterval("P1D"))->format("Y-m-d H:i:s"), $value);

        $evaluator = new SQLFilterValueEvaluator();
        $value = $evaluator->evaluateFilterValue("7_DAYS_AGO");
        $this->assertEquals((new \DateTime())->sub(new \DateInterval("P7D"))->format("Y-m-d H:i:s"), $value);

        $evaluator = new SQLFilterValueEvaluator();
        $value = $evaluator->evaluateFilterValue("5_HOURS_AGO");
        $this->assertEquals((new \DateTime())->sub(new \DateInterval("PT5H"))->format("Y-m-d H:i:s"), $value);

    }

}