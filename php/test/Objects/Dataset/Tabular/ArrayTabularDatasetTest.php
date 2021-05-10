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


}