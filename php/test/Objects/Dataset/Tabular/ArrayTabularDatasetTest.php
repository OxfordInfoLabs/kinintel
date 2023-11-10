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
        $this->assertFalse($arrayTabularDataSet->nextDataItem());

        $this->assertEquals([
            ["name" => "Mark", "age" => 30],
            ["name" => "Bob", "age" => 25],
            ["name" => "Mary", "age" => 50]
        ], $arrayTabularDataSet->getAllData());

    }


    public function testCanGetNextNItemsFromArrayTabularDataset() {
        $arrayTabularDataSet = new ArrayTabularDataset([
            new Field("name", "Name"),
            new Field("age", "Age")
        ], [
            ["name" => "Mark", "age" => 30],
            ["name" => "Bob", "age" => 25],
            ["name" => "Mary", "age" => 50],
            ["name" => "Clare", "age" => 12],
            ["name" => "Andrew", "age" => 3]
        ]);

        $this->assertEquals([
            ["name" => "Mark", "age" => 30],
            ["name" => "Bob", "age" => 25],
            ["name" => "Mary", "age" => 50]
        ], $arrayTabularDataSet->nextNDataItems(3));

        $this->assertEquals([
            ["name" => "Clare", "age" => 12]
        ], $arrayTabularDataSet->nextNDataItems(1));

        $this->assertEquals([
            ["name" => "Andrew", "age" => 3]
        ], $arrayTabularDataSet->nextNDataItems(50));
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
            new Field("date", "Date", "[[date | /^.{8}(.{2})/]]/[[date | /^.{5}(.{2})/]]/[[date | /^.{0,4}/]]"),
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


    public function testIfFieldWithFlattenArraySetItemsAreCreatedForEachArrayItem() {

        $arrayTabularDataSet = new ArrayTabularDataset([
            new Field("name", "Name"),
            new Field("note_id", "Note Id", null, Field::TYPE_STRING, false, true),
            new Field("note", "Note", null, Field::TYPE_STRING, false, true)
        ], [
            ["name" => "Mark", "note_id" => [1, 2, 3, 4, 5], "note" => ["Note 1", "Note 2", "Note 3", "Note 4", "Note 5"]],
            ["name" => "Bob", "note_id" => [6, 7, 8, 9, 10], "note" => ["Note 6", "Note 7", "Note 8", "Note 9", "Note 10"]],
            ["name" => "Mary", "note_id" => [], "note" => []]
        ]);

        // Confirm data
        $this->assertEquals(["name" => "Mark", "note_id" => 1, "note" => "Note 1"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Mark", "note_id" => 2, "note" => "Note 2"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Mark", "note_id" => 3, "note" => "Note 3"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Mark", "note_id" => 4, "note" => "Note 4"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Mark", "note_id" => 5, "note" => "Note 5"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Bob", "note_id" => 6, "note" => "Note 6"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Bob", "note_id" => 7, "note" => "Note 7"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Bob", "note_id" => 8, "note" => "Note 8"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Bob", "note_id" => 9, "note" => "Note 9"], $arrayTabularDataSet->nextDataItem());
        $this->assertEquals(["name" => "Bob", "note_id" => 10, "note" => "Note 10"], $arrayTabularDataSet->nextDataItem());
        $this->assertNull($arrayTabularDataSet->nextDataItem());
        $this->assertFalse($arrayTabularDataSet->nextDataItem());


    }


}