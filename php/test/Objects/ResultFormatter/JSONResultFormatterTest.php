<?php

namespace Kinintel\Objects\ResultFormatter;

use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
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

        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("value", "Value")], $result->getColumns());
        $this->assertEquals([["value" => "item1"], ["value" => "item2"], ["value" => "item3"]], $result->getAllData());


        // Array of values
        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode([
            ["Mark", 3, "Hello"],
            ["Bob", 7, "Bingo"]
        ])));


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("value1", "Value 1"), new Field("value2", "Value 2"), new Field("value3", "Value 3")], $result->getColumns());
        $this->assertEquals([["value1" => "Mark", "value2" => 3, "value3" => "Hello"],
            ["value1" => "Bob", "value2" => 7, "value3" => "Bingo"]], $result->getAllData());


        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode([
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ])));


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getAllData());


    }


    public function testColumnsPassedThroughToDatasetIfSuppliedToFormat() {


        $formatter = new JSONResultFormatter();


        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode([
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ])), [
            new Field("other_data", "Custom"), new Field("name")
        ]);


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("other_data", "Custom"), new Field("name", "Name")], $result->getColumns());
        $this->assertEquals([["other_data" => "Hello", "name" => "Mark"],
            ["other_data" => "Bingo", "name" => "Bob"]], $result->getAllData());


    }


    public function testDataSetIsReturnedCorrectlyForSingleItemsReturnedAtTopLevel() {

        $formatter = new JSONResultFormatter("", "", true);


        // Primitive single value
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(12345)));

        $this->assertInstanceOf(ArrayTabularDataset::class, $result);
        $this->assertEquals([new Field("value", "Value")], $result->getColumns());
        $this->assertEquals([["value" => 12345]], $result->getAllData());


        // Single unindexed object
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(
            ["Mark", 3, "Hello"]
        )));

        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("value1", "Value 1"), new Field("value2", "Value 2"), new Field("value3", "Value 3")], $result->getColumns());
        $this->assertEquals([["value1" => "Mark", "value2" => 3, "value3" => "Hello"]], $result->getAllData());


        // Single indexed object
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]
        )));


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]], $result->getAllData());


    }


    public function testResultsOffsetPathIsObservedIfSetInResultMappingConfig() {


        $formatter = new JSONResultFormatter("results");


        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(["results" => [
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ]])));


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getAllData());


        $formatter = new JSONResultFormatter("results[0].data[1].items");


        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(["results" => [
            ["data" => [[], ["items" => [
                ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
                ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
            ]]]],
            ["items" => []]
        ]])));


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getAllData());


    }


    public function testItemOffsetPathIsObservedIfSetInResultMappingConfig() {

        $formatter = new JSONResultFormatter("", "drilled.down");


        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode([
            ["drilled" => ["down" => ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]]],
            ["drilled" => ["down" => ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]]]
        ])));


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getAllData());

    }


    public function testOffsetAndLimitIsImplementedWhenSupplied() {

        $formatter = new JSONResultFormatter("results");


        // Limited to 1 result
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(["results" => [
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ]])), [], 1);


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]], $result->getAllData());


        // Offset by 1
        $result = $formatter->format(new ReadOnlyStringStream(json_encode(["results" => [
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ]])), [], 10, 1);


        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other Data")], $result->getColumns());
        $this->assertEquals([["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getAllData());


    }


    public function testRawResultsAddedAsColumnIfPropertySuppliedToConfig() {

        $formatter = new JSONResultFormatter("", "drilled.down", false, "mickeyMouse");

        $fullObject = [
            ["drilled" => ["down" => ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]]],
            ["drilled" => ["down" => ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]]]
        ];

        // Regular key / value pairs
        $result = $formatter->format(new ReadOnlyStringStream(json_encode($fullObject)));


        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"),
            new Field("other_data", "Other Data"), new Field("mickeyMouse")], $result->getColumns());

        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello", "mickeyMouse" => $fullObject],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo", "mickeyMouse" => $fullObject]], $result->getAllData());


    }

}
