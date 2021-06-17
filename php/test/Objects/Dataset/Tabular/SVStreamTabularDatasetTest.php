<?php


namespace Kinintel\Test\Objects\Dataset\Tabular;

use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class SVStreamTabularDatasetTest extends \PHPUnit\Framework\TestCase {

    public function testCanGetNextItemDataUsingStreamWhenFirstRowNotHeader() {

        // Create mock stream
        $mockStream = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $dataSet = new SVStreamTabularDataSet([
        ], $mockStream, false, ",", '"');

        $mockStream->returnValue("readCSVLine", [
            "Mark", 22
        ], [
            ",", '"'
        ]);

        $nextValue = $dataSet->nextDataItem();
        $this->assertEquals([
            "column1" => "Mark",
            "column2" => 22
        ], $nextValue);


    }

    public function testCanGetNextItemDataUsingStreamWhenFirstRowIsHeader() {

        // Create mock stream
        $mockStream = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $mockStream->returnValue("readCSVLine", [
            "name", "age"
        ], [
            ",", '"'
        ]);

        $dataSet = new SVStreamTabularDataSet([
        ], $mockStream, true, ",", '"');


        $nextValue = $dataSet->nextDataItem();
        $this->assertEquals([
            "name" => "name",
            "age" => "age"
        ], $nextValue);


    }


}