<?php


namespace Kinintel\Test\Services\Alert\MatchRule;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Alert\MatchRule\AlertMatchRule;
use Kinintel\Services\Alert\MatchRule\RowCountAlertMatchRule;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Alert\MatchRule\RowCountAlertMatchRuleConfiguration;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

/**
 * Test cases for the row count alert match rule
 *
 * Class RowCountAlertMatchRuleTest
 * @package Kinintel\Test\Services\Alert\MatchRule
 */
class RowCountAlertMatchRuleTest extends TestBase {

    /**
     * @var RowCountAlertMatchRule
     */
    private $matchRule;


    public function setUp(): void {
        $this->matchRule = Container::instance()->getInterfaceImplementation(AlertMatchRule::class, "rowcount");
    }

    public function testEqualsRuleMatchedForPassedDataset() {

        $dataSet = new ArrayTabularDataset([
            new Field("data")
        ], [
            ["data" => "Item 1"],
            ["data" => "Item 2"],
        ]);

        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(0)));
        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(1)));
        $this->assertTrue($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(2)));
        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(3)));


    }


    public function testGreaterThanRuleMatchedForPassedDataSet(){
        $dataSet = new ArrayTabularDataset([
            new Field("data")
        ], [
            ["data" => "Item 1"],
            ["data" => "Item 2"],
        ]);

        $this->assertTrue($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(0, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_GREATER_THAN)));
        $this->assertTrue($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(1, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_GREATER_THAN)));
        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(2, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_GREATER_THAN)));
        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(3, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_GREATER_THAN)));

    }


    public function testLessThanRuleMatchedForPassedDataSet(){
        $dataSet = new ArrayTabularDataset([
            new Field("data")
        ], [
            ["data" => "Item 1"],
            ["data" => "Item 2"],
        ]);

        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(0, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_LESS_THAN)));
        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(1, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_LESS_THAN)));
        $this->assertFalse($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(2, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_LESS_THAN)));
        $this->assertTrue($this->matchRule->matchesRule($dataSet, new RowCountAlertMatchRuleConfiguration(3, RowCountAlertMatchRuleConfiguration::MATCH_TYPE_LESS_THAN)));

    }

}