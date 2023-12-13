<?php


namespace Kinintel\Test\Services\Util;


use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\TestBase;

include_once "autoloader.php";

class ParameterisedStringEvaluatorTest extends TestBase {


    public function testCanEvaluateParameterisedStringWithParametersAndFieldValues() {

        /** @var ParameterisedStringEvaluator $evaluator */
        $evaluator = Container::instance()->get(ParameterisedStringEvaluator::class);

        $result = $evaluator->evaluateString("Hello [[string1]], [[date1 | year]] is the {{date2 | monthName}} {{string2}} correct", [
            "string1" => "World",
            "date1" => "2020-01-01"
        ], [
            "date2" => "2020-05-02",
            "string2" => "Actually"
        ]);

        $this->assertEquals("Hello World, 2020 is the May Actually correct", $result);


    }
    

}