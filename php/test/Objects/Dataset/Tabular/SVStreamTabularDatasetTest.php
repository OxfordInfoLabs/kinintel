<?php


namespace Kinintel\Test\Objects\Dataset\Tabular;

use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;

include_once "autoloader.php";

class SVStreamTabularDatasetTest extends \PHPUnit\Framework\TestCase {

    public function testCanGetNextItemDataUsingStreamWhenFirstRowNotHeader() {

        // Create mock stream
        $mockStream = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $dataSet = new SVStreamTabularDataSet([
        ], $mockStream, 0, false, ",", '"');

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
        ], $mockStream, 0, true, ",", '"');


        $nextValue = $dataSet->nextDataItem();
        $this->assertEquals([
            "name" => "name",
            "age" => "age"
        ], $nextValue);


    }

    public function testIfIgnoreColumnIndexesSuppliedThoseColumnsAreNotIncludedInOutput() {

        // Create mock stream
        $mockStream = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $mockStream->returnValue("readCSVLine", [
            "value1", "value2", "value3", "value4", "value5", "value6"
        ], [
            ",", '"'
        ]);

        $dataSet = new SVStreamTabularDataSet([
        ], $mockStream, 0, false, ",", '"', PHP_INT_MAX, 0, [
            1, 3, 5
        ]);

        $this->assertEquals(["column1" => "value1", "column2" => "value3", "column3" => "value5"], $dataSet->nextDataItem());


    }


    public function testIfSkipBlankColumnValuesSuppliedTheyAreOptimisedOut() {

        // Create mock stream
        $mockStream = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $mockStream->returnValue("readCSVLine", [
            "value1", "", "value3", "", "value5", "value6"
        ], [
            ",", '"'
        ]);

        $dataSet = new SVStreamTabularDataSet([
        ], $mockStream, 0, false, ",", '"', PHP_INT_MAX, 0, [], true, true);

        $this->assertEquals(["column1" => "value1", "column2" => "value3", "column3" => "value5", "column4" => "value6"], $dataSet->nextDataItem());


    }

    public function testCanSkipRowsByRegex() {

        // Create mock stream
        $mockStream = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $dataSet = new SVStreamTabularDataSet([
        ], $mockStream, 0, false, ",", '"',PHP_INT_MAX,0,[],true,false,"/^[!]/");

        $mockStream->returnValue("readCSVLine", [
            "!Mark", 22
        ], [
            ",", '"'
        ]);

        $nextValue = $dataSet->nextDataItem();
        $this->assertEquals(false, $nextValue);

        // Create mock stream
        $mockStream2 = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $dataSet = new SVStreamTabularDataSet([
        ], $mockStream2, 0, false, ",", '"',PHP_INT_MAX,0,[],true,false,"/^[#:]/");

        $mockStream2->returnValue("readCSVLine", [
            "#Mark", 22
        ], [
            ",", '"'
        ]);

        $nextValue = $dataSet->nextDataItem();
        $this->assertEquals(false, $nextValue);

    }


}