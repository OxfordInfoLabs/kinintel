<?php

namespace Kinintel\Objects\Datasource\Google;


use GuzzleHttp\Psr7\Stream;
use Kinintel\Objects\ResultFormatter\SVResultFormatter;
use Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Google\GoogleBucketFileDatasourceConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class GoogleBucketFileDatasourceTest extends TestCase {

    public function testForSingleFileConfiguredDatasourceDatasetReturnedCorrectlyOnMaterialise() {

        $config = new GoogleBucketFileDatasourceConfig("kinintel-unit-test", null, "TestFiles/test1.csv");
        $config->setResultFormat("sv");
        $config->setResultFormatConfig(new SVResultFormatter());
        $config->setColumns([
            new Field("name"),
            new Field("age")
        ]);

        $authenticationCredentials = new GoogleCloudCredentials(file_get_contents(__DIR__ . "/google-test.json"));

        $dataSource = new GoogleBucketFileDatasource($config, $authenticationCredentials);

        $dataSet = $dataSource->materialise();

        $this->assertEquals([
            new Field("name"),
            new Field("age")
        ], $dataSet->getColumns());

        $this->assertEquals(
            ["name" => "John", "age" => 30], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "James", "age" => 22], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "Robert", "age" => 50], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "William", "age" => 42], $dataSet->nextDataItem());

    }

    public function testForMultipleFileConfiguredDatasourceDatasetReturnedCorrectlyOnMaterialise() {

        $config = new GoogleBucketFileDatasourceConfig("kinintel-unit-test", "TestFiles", null);
        $config->setResultFormat("sv");
        $config->setResultFormatConfig(new SVResultFormatter());
        $config->setColumns([
            new Field("name"),
            new Field("age")
        ]);

        $authenticationCredentials = new GoogleCloudCredentials(file_get_contents(__DIR__ . "/google-test.json"));

        $dataSource = new GoogleBucketFileDatasource($config, $authenticationCredentials);

        $dataSet = $dataSource->materialise();

        $this->assertEquals([
            new Field("name"),
            new Field("age")
        ], $dataSet->getColumns());


        $this->assertEquals(
            ["name" => "John", "age" => 30], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "James", "age" => 22], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "Robert", "age" => 50], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "William", "age" => 42], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "Philip", "age" => 86], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "Stephen", "age" => 71], $dataSet->nextDataItem());

        $this->assertEquals(
            ["name" => "Norman", "age" => 51], $dataSet->nextDataItem());

    }


    public function testCanEvaluteParameterisedFolderName() {
        $config = new GoogleBucketFileDatasourceConfig("kinintel-unit-test", "{{2_DAYS_AGO | dateConvert 'Y-m-d H:i:s' 'Ym'}}", null);
        $config->setResultFormat("sv");
        $config->setResultFormatConfig(new SVResultFormatter());
        $config->setColumns([
            new Field("name"),
            new Field("age")
        ]);


        $month = new \DateTime();
        $month->sub(new \DateInterval("P2D"));

        $this->assertEquals($month->format("Ym"), $config->getFolder());
    }
}