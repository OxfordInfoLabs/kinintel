<?php

namespace Kinintel\Test\Objects\ResultFormatter;

use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\ResultFormatter\XMLResultFormatter;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\WebScraper\FieldWithXPathSelector;
use Kinintel\ValueObjects\ResultFormatter\XPathTarget;

include_once "autoloader.php";


class XMLResultFormatterTest extends \PHPUnit\Framework\TestCase {

    public function testCanFormatAnXMLDocumentUsingASimpleItemXPath() {

        $formatter = new XMLResultFormatter("//contact");

        // Primitive array
        $result = $formatter->format(new ReadOnlyStringStream("<data>
       <contact>
       <name>Bobby</name>
       <age>23</age>
       <notes>Loves Gardening</notes>
</contact>
<contact>
       <name>Mark</name>
       <age>44</age>
       <notes>Piano Player</notes>
</contact>
<contact>
       <name>Eileen</name>
       <age>55</age>
       <notes>Shopping Freak</notes>
</contact>
</data>"));

        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name"), new Field("age"), new Field("notes")], $result->getColumns());
        $this->assertEquals([
            ["name" => "Bobby", "age" => 23, "notes"=> "Loves Gardening"],
            ["name" => "Mark", "age" => 44, "notes"=> "Piano Player"],
            ["name" => "Eileen", "age" => 55, "notes"=> "Shopping Freak"]
        ], $result->getAllData());


    }



    public function testPrefixesAreRemovedFromItemValueElementsWhenMappingToFields() {

        $formatter = new XMLResultFormatter("//test:contact");

        // Primitive array
        $result = $formatter->format(new ReadOnlyStringStream("<test:data xmlns:test='https://test.com'>
       <test:contact>
       <test:name>Bobby</test:name>
       <test:age>23</test:age>
       <test:notes>Loves Gardening</test:notes>
</test:contact>
<test:contact>
       <test:name>Mark</test:name>
       <test:age>44</test:age>
       <test:notes>Piano Player</test:notes>
</test:contact>
<test:contact>
       <test:name>Eileen</test:name>
       <test:age>55</test:age>
       <test:notes>Shopping Freak</test:notes>
</test:contact>
</test:data>"));

        $this->assertInstanceOf(ArrayTabularDataset::class, $result);

        $this->assertEquals([new Field("name"), new Field("age"), new Field("notes")], $result->getColumns());
        $this->assertEquals([
            ["name" => "Bobby", "age" => 23, "notes"=> "Loves Gardening"],
            ["name" => "Mark", "age" => 44, "notes"=> "Piano Player"],
            ["name" => "Eileen", "age" => 55, "notes"=> "Shopping Freak"]
        ], $result->getAllData());


    }

    public function testNamespacesAreRegistered() {
        $badFormatter = new XMLResultFormatter("//test:contact");
        $goodFormatter = new XMLResultFormatter("//test:contact", ["test" => "https://test.com"]);
        $xml = <<<XML
<data xmlns='https://test.com'>
    <contact>
       <name>Bobby</name>
       <age>23</age>
       <notes>Loves Gardening</notes>
    </contact>
    <contact>
       <name>Mark</name>
       <age>44</age>
       <notes>Piano Player</notes>
    </contact>
</data>
XML;
        $result = $goodFormatter->format(new ReadOnlyStringStream($xml));
        $this->assertEquals([new Field("name"), new Field("age"), new Field("notes")], $result->getColumns());
        $this->assertEquals([
            ["name" => "Bobby", "age" => 23, "notes"=> "Loves Gardening"],
            ["name" => "Mark", "age" => 44, "notes"=> "Piano Player"],
        ], $result->getAllData());

        try {
            $result = $badFormatter->format(new ReadOnlyStringStream($xml));
            $this->fail();
        } catch (\Exception $exception) {
            $this->assertStringContainsString("Undefined namespace prefix", $exception->getMessage());
            //Success
        }
    }

    public function testCanRetrieveItemsFromInnerXPaths() {
        $formatter = new XMLResultFormatter("//test:contact", ["test" => "https://test.com"],
            [
                new XPathTarget("id", ".", "id"),
                new XPathTarget("name", "test:name"),
                new XPathTarget("dob_raw", "test:dob"),
                new XPathTarget("notes", "test:div[@class='notes']"),
            ],
        );
        $xml = <<<XML
<data xmlns='https://test.com'>
    <contact id="1">
       <name>Bobby</name>
       <dob>1999-01-01</dob>
       <div class="notes">Loves Gardening</div>
    </contact>
    <contact id="2">
       <name>Mark</name>
       <dob>2024-12-25</dob>
       <div class="notes">Piano Player</div>
    </contact>
</data>
XML;
        $columns = [
            new Field("id"),
            new Field("name"),
            new Field("date_of_birth", valueExpression: "[[ dob_raw | date 'd-MM-YYYY' ]]"),
            new Field("notes"),
        ];
        $result = $formatter->format(new ReadOnlyStringStream($xml), $columns);

        $expectedData = [
            ["id" => 1, "name" => "Bobby", "date_of_birth" => "01-01-1999", "notes" => "Loves Gardening"],
            ["id" => 2, "name" => "Mark", "date_of_birth" => "25-12-2024", "notes" => "Piano Player"],
        ];
        $this->assertEquals($columns, $result->getColumns());
        $this->assertEquals($expectedData, $result->getAllData());
    }
    public function testCanParseHTML(){
        $formatter = new XMLResultFormatter("//div[@class='contact']", [],
            [
                new XPathTarget("id", ".", "id"),
                new XPathTarget("name", "span"),
                new XPathTarget("dob_raw", "p"),
                new XPathTarget("notes", "div[@class='notes']"),
            ], true
        );
        $xml = <<<XML
<html><body>
    <div class="contact" id="1">
       <span>Bobby</span>
       <p>1999-01-01</p>
       <div class="notes">Loves Gardening</div>
    </div>
    <div class="contact" id="2">
       <span>Mark</span>
       <p>2024-12-25</p>
       <div class="notes">Piano Player</div>
    </div>
</body></html>
XML;
        $columns = [
            new Field("id"),
            new Field("name"),
            new Field("date_of_birth", valueExpression: "[[ dob_raw | date 'd-MM-YYYY' ]]"),
            new Field("notes"),
        ];
        $result = $formatter->format(new ReadOnlyStringStream($xml), $columns);

        $expectedData = [
            ["id" => 1, "name" => "Bobby", "date_of_birth" => "01-01-1999", "notes" => "Loves Gardening"],
            ["id" => 2, "name" => "Mark", "date_of_birth" => "25-12-2024", "notes" => "Piano Player"],
        ];
        $this->assertEquals($columns, $result->getColumns());
        $this->assertEquals($expectedData, $result->getAllData());
    }
}