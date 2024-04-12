<?php

namespace Kinintel\Test\Objects\ResultFormatter;

use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\ResultFormatter\XMLResultFormatter;
use Kinintel\ValueObjects\Dataset\Field;

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

        $formatter = new XMLResultFormatter("//test:contact", "test:");

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


}