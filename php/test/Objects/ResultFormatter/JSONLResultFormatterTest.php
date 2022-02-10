<?php


namespace Kinintel\Test\Objects\ResultFormatter;


use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinintel\Objects\ResultFormatter\JSONLResultFormatter;
use Kinintel\ValueObjects\Dataset\Field;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class JSONLResultFormatterTest extends TestCase {

    public function testDatasetIsReturnedCorrectlyForDefaultJSONLinesFileContents() {

        $formatter = new JSONLResultFormatter();
        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-jsonl.jsonl"));

        $this->assertEquals([
            [
                "name" => "Mark",
                "age" => "44",
                "position" => "Director"
            ], [
                "name" => "Bob",
                "age" => "55",
                "position" => "CEO"
            ], [
                "name" => "Mary",
                "age" => "23",
                "position" => "HR"
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("name", "Name"),
            new Field("age", "Age"),
            new Field("position", "Position")
        ], $results->getColumns());


    }

    public function testDatasetIsReturnedCorrectlyWhenExplicitColumnsSupplied() {
        $formatter = new JSONLResultFormatter();
        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-jsonl.jsonl"), [
            new Field("age"),
            new Field("position")
        ]);

        $this->assertEquals([
            [
                "age" => "44",
                "position" => "Director"
            ], [
                "age" => "55",
                "position" => "CEO"
            ], [
                "age" => "23",
                "position" => "HR"
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("age", "Age"),
            new Field("position", "Position")
        ], $results->getColumns());


    }


    public function testStartOffsetIsRespectedIfPassedToResults() {
        $formatter = new JSONLResultFormatter(null, 2);
        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-jsonl.jsonl"));

        $this->assertEquals([
            [
                "name" => "Mary",
                "age" => "23",
                "position" => "HR"
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("name", "Name"),
            new Field("age", "Age"),
            new Field("position", "Position")
        ], $results->getColumns());
    }


    public function testItemOffsetPathIsEvaluatedIfPassedToResults() {

        $formatter = new JSONLResultFormatter("results.data");
        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-jsonl-with-offset-path.jsonl"));

        $this->assertEquals([
            [
                "name" => "Mark",
                "age" => "44",
                "position" => "Director"
            ], [
                "name" => "Bob",
                "age" => "55",
                "position" => "CEO"
            ], [
                "name" => "Mary",
                "age" => "23",
                "position" => "HR"
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("name", "Name"),
            new Field("age", "Age"),
            new Field("position", "Position")
        ], $results->getColumns());

    }


}