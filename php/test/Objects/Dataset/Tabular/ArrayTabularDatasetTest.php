<?php


namespace Kinintel\Objects\Dataset\Tabular;

use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class ArrayTabularDatasetTest extends \PHPUnit\Framework\TestCase {


    public function testCanGetNextItemsAndAllDataFromArrayTabularDataset() {

        $arrayTabularDataSet = new ArrayTabularDataset([
            new Field("name", "Name"),
            new Field("age", "Age")
        ], [
            ["name" => "Mark", "age" => 30],
            ["name" => "Bob", "age" => 25],
            ["name" => "Mary", "age" => 50]
        ]);

        $this->assertEquals(["name" => "Mark", "age" => 30], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Bob", "age" => 25], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Mary", "age" => 50], $arrayTabularDataSet->nextDataItem());
        $this->assertNull($arrayTabularDataSet->nextDataItem());

        $this->assertEquals([
            ["name" => "Mark", "age" => 30],
            ["name" => "Bob", "age" => 25],
            ["name" => "Mary", "age" => 50]
        ], $arrayTabularDataSet->getAllData());

    }


    public function testNextDataItemUsesColumnsToFilterItemData() {

        $arrayTabularDataSet = new ArrayTabularDataset([
            new Field("name", "Name"),
            new Field("age", "Age")
        ], [
            ["name" => "Mark", "age" => 30, "telephone" => "07546 787878"],
            ["name" => "Bob", "age" => 25, "telephone" => "07546 787878"],
            ["name" => "Mary", "age" => 50, "telephone" => "07546 787878"]
        ]);

        $this->assertEquals([
            ["name" => "Mark", "age" => 30],
            ["name" => "Bob", "age" => 25],
            ["name" => "Mary", "age" => 50]
        ], $arrayTabularDataSet->getAllData());


    }


    public function testIfFieldWithStaticValueExpressionSuppliedItsValueIsMergedIntoDataAsColumn() {

        $arrayTabularDataSet = new ArrayTabularDataset([
            new Field("type", "Type", "Person"),
            new Field("name", "Name"),
            new Field("age", "Age")
        ], [
            ["name" => "Mark", "age" => 30, "telephone" => "07546 787878"],
            ["name" => "Bob", "age" => 25, "telephone" => "07546 787878"],
            ["name" => "Mary", "age" => 50, "telephone" => "07546 787878"]
        ]);

        $this->assertEquals([
            ["type" => "Person", "name" => "Mark", "age" => 30],
            ["type" => "Person", "name" => "Bob", "age" => 25],
            ["type" => "Person", "name" => "Mary", "age" => 50]
        ], $arrayTabularDataSet->getAllData());

    }


    public function testIfFieldWithRegularExpressionValueExpressionSuppliedItIsEvaluatedAndMergedIntoDataAsColumn() {


        $arrayTabularDataSet = new ArrayTabularDataset([
            new Field("type", "Type", "Person"),
            new Field("name", "Name"),
            new Field("age", "Age"),
            new Field("date", "Date", "[[date:/^.{8}(.{2})/]]/[[date:/^.{5}(.{2})/]]/[[date:/^.{0,4}/]]"),
            new Field("otherName", "Other Name", "[[name]]")
        ], [
            ["name" => "Mark", "age" => 30, "telephone" => "07546 787878", "date" => "2020-01-01"],
            ["name" => "Bob", "age" => 25, "telephone" => "07546 787878", "date" => "2019-05-03"],
            ["name" => "Mary", "age" => 50, "telephone" => "07546 787878", "date" => "2018-03-02"]
        ]);

        $this->assertEquals([
            ["type" => "Person", "name" => "Mark", "age" => 30, "date" => "01/01/2020", "otherName" => "Mark"],
            ["type" => "Person", "name" => "Bob", "age" => 25, "date" => "03/05/2019", "otherName" => "Bob"],
            ["type" => "Person", "name" => "Mary", "age" => 50, "date" => "02/03/2018", "otherName" => "Mary"]
        ], $arrayTabularDataSet->getAllData());


    }


}