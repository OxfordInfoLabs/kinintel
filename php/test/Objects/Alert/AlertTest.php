<?php


namespace Kinintel\Test\Objects\Alert;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Alert\Alert;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Services\Alert\MatchRule\AlertMatchRule;

include_once "autoloader.php";

class AlertTest extends \PHPUnit\Framework\TestCase {

    public function testCanEvaluateMatchRuleOnAlertForPassedDataset() {

        $testMatchRule = MockObjectProvider::instance()->getMockInstance(AlertMatchRule::class);
        $testMatchRule->returnValue("getConfigClass", TestAlertMatchRuleConfiguration::class);
        Container::instance()->addInterfaceImplementation(AlertMatchRule::class, "test", get_class($testMatchRule));
        Container::instance()->set(get_class($testMatchRule), $testMatchRule);

        $dataset = MockObjectProvider::instance()->getMockInstance(TabularDataset::class);

        $testMatchRule->returnValue("matchesRule", true, [
            $dataset, new TestAlertMatchRuleConfiguration("Mark", "Jim")
        ]);

        $alert = new Alert("test", ["property1" => "Mark", "property2" => "Jim"]);


        $this->assertTrue($alert->evaluateMatchRule($dataset));

    }

}
