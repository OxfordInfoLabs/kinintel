<?php

namespace Kinintel\Objects\ResultFormatter;

use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\TabularDataset;

include_once "autoloader.php";

class JSONResultFormatterTest extends \PHPUnit\Framework\TestCase {

    public function testDataSetIsReturnedCorrectlyForDefaultArraysReturnedAtTopLevel() {

        $formatter = new JSONResultFormatter();

        // Primitive array
        $result = $formatter->format(new ReadOnlyStringStream(json_encode([
            "item1",
            "item2",
            "item3"
        ])));

        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("value", "Value")], $result->getColumns());
        $this->assertEquals([["value" => "item1"], ["value" => "item2"], ["value" => "item3"]], $result->getData());


        // Array of values
        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode([
            ["Mark", 3, "Hello"],
            ["Bob", 7, "Bingo"]
        ])));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("value1", "Value 1"), new Field("value2", "Value 2"), new Field("value3", "Value 3")], $result->getColumns());
        $this->assertEquals([["value1" => "Mark", "value2" => 3, "value3" => "Hello"],
            ["value1" => "Bob", "value2" => 7, "value3" => "Bingo"]], $result->getData());


        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode([
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ])));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getData());


    }


    public function testDataSetIsReturnedCorrectlyForSingleItemsReturnedAtTopLevel() {

        $formatter = new JSONResultFormatter("", true);


        // Primitive single value
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(12345)));

        $this->assertInstanceOf(TabularDataset::class, $result);
        $this->assertEquals([new Field("value", "Value")], $result->getColumns());
        $this->assertEquals([["value" => 12345]], $result->getData());


        // Single unindexed object
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(
            ["Mark", 3, "Hello"]
        )));

        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("value1", "Value 1"), new Field("value2", "Value 2"), new Field("value3", "Value 3")], $result->getColumns());
        $this->assertEquals([["value1" => "Mark", "value2" => 3, "value3" => "Hello"]], $result->getData());


        // Single indexed object
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]
        )));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]], $result->getData());


    }


    public function testResultPathIsObservedIfSetInResultMappingConfig() {


        $formatter = new JSONResultFormatter("results");


        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(["results" => [
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ]])));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getData());


    }

}