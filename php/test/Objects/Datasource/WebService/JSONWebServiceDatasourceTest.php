<?php


namespace Kinintel\Objects\Datasource\WebService;

use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\TabularDataset;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceDataSourceConfig;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceResultMapping;

include_once "autoloader.php";

class JSONWebServiceDatasourceTest extends \PHPUnit\Framework\TestCase {


    public function testDataSetIsReturnedCorrectlyForDefaultArraysReturnedAtTopLevel() {

        $config = new JSONWebServiceDataSourceConfig("http://google.com");
        $webServiceDataSource = new JSONWebServiceDatasource($config);


        // Primitive array
        $result = $webServiceDataSource->materialiseWebServiceResult(json_encode([
            "item1",
            "item2",
            "item3"
        ]));

        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("value", "Value")], $result->getColumns());
        $this->assertEquals([["value" => "item1"], ["value" => "item2"], ["value" => "item3"]], $result->getData());


        // Array of values
        // Regular key / value pairs
        $result = $webServiceDataSource->materialiseWebServiceResult(json_encode([
            ["Mark", 3, "Hello"],
            ["Bob", 7, "Bingo"]
        ]));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("value1", "Value 1"), new Field("value2", "Value 2"), new Field("value3", "Value 3")], $result->getColumns());
        $this->assertEquals([["value1" => "Mark", "value2" => 3, "value3" => "Hello"],
            ["value1" => "Bob", "value2" => 7, "value3" => "Bingo"]], $result->getData());


        // Regular key / value pairs
        $result = $webServiceDataSource->materialiseWebServiceResult(json_encode([
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ]));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getData());


    }


    public function testDataSetIsReturnedCorrectlyForSingleItemsReturnedAtTopLevel() {

        $config = new JSONWebServiceDataSourceConfig("http://google.com");
        $config->setResultMapping(new JSONWebServiceResultMapping("", true));
        $webServiceDataSource = new JSONWebServiceDatasource($config);


        // Primitive single value
        $result = $webServiceDataSource->materialiseWebServiceResult(json_encode(12345));

        $this->assertInstanceOf(TabularDataset::class, $result);
        $this->assertEquals([new Field("value", "Value")], $result->getColumns());
        $this->assertEquals([["value" => 12345]], $result->getData());


        // Single unindexed object
        $result = $webServiceDataSource->materialiseWebServiceResult(json_encode(
            ["Mark", 3, "Hello"]
        ));

        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("value1", "Value 1"), new Field("value2", "Value 2"), new Field("value3", "Value 3")], $result->getColumns());
        $this->assertEquals([["value1" => "Mark", "value2" => 3, "value3" => "Hello"]], $result->getData());


        // Single indexed object
        $result = $webServiceDataSource->materialiseWebServiceResult(json_encode(
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]
        ));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"]], $result->getData());


    }


    public function testResultPathIsObservedIfSetInResultMappingConfig() {


        $config = new JSONWebServiceDataSourceConfig("http://google.com");
        $config->setResultMapping(new JSONWebServiceResultMapping("results"));
        $webServiceDataSource = new JSONWebServiceDatasource($config);

        // Regular key / value pairs
        $result = $webServiceDataSource->materialiseWebServiceResult(json_encode(["results" => [
            ["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]
        ]]));


        $this->assertInstanceOf(TabularDataset::class, $result);

        $this->assertEquals([new Field("name", "Name"), new Field("ageAtReg", "Age At Reg"), new Field("other_data", "Other data")], $result->getColumns());
        $this->assertEquals([["name" => "Mark", "ageAtReg" => 3, "other_data" => "Hello"],
            ["name" => "Bob", "ageAtReg" => 7, "other_data" => "Bingo"]], $result->getData());


    }


}