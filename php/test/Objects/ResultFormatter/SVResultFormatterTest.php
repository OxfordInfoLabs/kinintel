<?php

namespace Kinintel\Objects\ResultFormatter;

use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\TabularDataset;

include_once "autoloader.php";

class SVResultFormatterTest extends \PHPUnit\Framework\TestCase {

    public function testFormatProcessesSimpleDefaultCSVFileContents() {

        $formatter = new SVResultFormatter();

        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-csv.csv"));

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $results);

        $this->assertEquals([
            [
                "column1" => "Mark",
                "column2" => "Robertshaw",
                "column3" => 30
            ], [
                "column1" => "James",
                "column2" => "Smith",
                "column3" => 10
            ], [
                "column1" => "David John",
                "column2" => "Wright",
                "column3" => 20
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3")
        ], $results->getColumns());


    }

    public function testFormatProcessesTabSeparatedValuesWhenConfigured() {

        $formatter = new SVResultFormatter("\t");

        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-tsv.txt"));

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $results);


        $this->assertEquals([
            [
                "column1" => "Mark",
                "column2" => "Robertshaw",
                "column3" => 30
            ], [
                "column1" => "James",
                "column2" => "Smith",
                "column3" => 10
            ], [
                "column1" => "David John",
                "column2" => "Wright",
                "column3" => 20
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3")
        ], $results->getColumns());


    }


    public function testIfFirstRowOffsetSuppliedResultSetIsMovedToThisRowBeforeProcessing() {

        $formatter = new SVResultFormatter("\t", '"', 2);

        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-tsv.txt"));

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $results);


        $this->assertEquals([
            [
                "column1" => "David John",
                "column2" => "Wright",
                "column3" => 20
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("column1", "Column 1"),
            new Field("column2", "Column 2"),
            new Field("column3", "Column 3")
        ], $results->getColumns());

    }


    public function testIfFirstRowSuppliedAsHeaderTheseAreUsedAsColumnTitlesAndCompressedAsNames() {

        $formatter = new SVResultFormatter(",", '"', 0, true);

        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-csv-with-headers.csv"));

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $results);


        $this->assertEquals([
            [
                "nameOfPerson" => "Mark",
                "surname" => "Robertshaw",
                "currentAge" => 30
            ], [
                "nameOfPerson" => "James",
                "surname" => "Smith",
                "currentAge" => 10
            ], [
                "nameOfPerson" => "David John",
                "surname" => "Wright",
                "currentAge" => 20
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("nameOfPerson", "Name Of Person"),
            new Field("surname", "Surname"),
            new Field("currentAge", "Current Age")
        ], $results->getColumns());


    }


    public function testIfColumnsPassedExplicitlyTheseArePassedToDataSet() {

        $formatter = new SVResultFormatter(",", '"', 0, true);

        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-csv-with-headers.csv"), [
            new Field("currentAge", "Age")
        ]);

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $results);


        $this->assertEquals([
            [
                "currentAge" => 30
            ], [
                "currentAge" => 10
            ], [
                "currentAge" => 20
            ]

        ], $results->getAllData());

        $this->assertEquals([
            new Field("currentAge", "Age")
        ], $results->getColumns());


    }


    public function testOffsetAndLimitObservedIfPassedIn() {

        $formatter = new SVResultFormatter(",", '"', 0, true);


        // Limit first
        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-csv-with-headers.csv"), [], 2);

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $results);


        $this->assertEquals([
            [
                "nameOfPerson" => "Mark",
                "surname" => "Robertshaw",
                "currentAge" => 30
            ], [
                "nameOfPerson" => "James",
                "surname" => "Smith",
                "currentAge" => 10
            ]

        ], $results->getAllData());


        // Offset one next
        $results = $formatter->format(new ReadOnlyFileStream(__DIR__ . "/test-csv-with-headers.csv"), [], 2, 2);

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $results);


        $this->assertEquals([
            [
                "nameOfPerson" => "David John",
                "surname" => "Wright",
                "currentAge" => 20
            ]
        ], $results->getAllData());


    }

}