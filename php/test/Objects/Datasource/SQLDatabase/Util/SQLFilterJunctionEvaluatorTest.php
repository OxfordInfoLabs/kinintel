<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\Util;

use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;

include_once "autoloader.php";


class SQLFilterJunctionEvaluatorTest extends \PHPUnit\Framework\TestCase {


    public function testCanEvaluateSimpleFilterJunctionToSQLForAllFilterTypes() {
        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator();

        // Simple equals filter
        $this->assertEquals([
            "sql" => "name = ?",
            "parameters" => [
                "Joe Bloggs"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("name", "Joe Bloggs")
        ])));


    }

}