<?php


namespace Kinintel\Test\Objects\Dataset\Tabular;

use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\ValueObjects\Dataset\Field;

include_once "autoloader.php";

class SVStreamTabularDatasetTest extends \PHPUnit\Framework\TestCase {

    public function testCanGetNextItemDataUsingStream() {

        // Create mock stream
        $mockStream = MockObjectProvider::instance()->getMockInstance(ReadableStream::class);

        $dataSet = new SVStreamTabularDataSet([
            new Field("name", "Name"),
            new Field("age", "Age")
        ], $mockStream, ",", '"');

        $mockStream->returnValue("readCSVLine", [
            "Mark", 22
        ], [
            ",", '"'
        ]);

        $nextValue = $dataSet->nextDataItem();
        $this->assertEquals([
            "name" => "Mark",
            "age" => 22
        ], $nextValue);


    }

}