<?php

namespace Kinintel\Test\ValueObjects\Datasource\Update;


use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\TypeConfig\NumericFieldTypeConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateFieldValidatorConfig;

include_once "autoloader.php";

class DatasourceUpdateFieldTest extends \PHPUnit\Framework\TestCase {

    public function testExplicitValidatorConfigsAreUsedToValidateFieldValues() {

        $datasourceUpdateField = new DatasourceUpdateField("test");
        $datasourceUpdateField->setValidatorConfigs([
            new DatasourceUpdateFieldValidatorConfig("required", []),
            new DatasourceUpdateFieldValidatorConfig("numeric", ["allowDecimals" => true])
        ]);

        $this->assertEquals(true, $datasourceUpdateField->validateValue("12"));
        $this->assertEquals(true, $datasourceUpdateField->validateValue("12.5"));
        $this->assertSame("Invalid numeric value supplied for test", $datasourceUpdateField->validateValue("Pink"));
        $this->assertSame("Value required for test", $datasourceUpdateField->validateValue(""));

    }

    public function testImplicitTypeBasedValidatorsAreUsedToValidateFieldValues() {

        // Integers should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_INTEGER);
        $this->assertSame(true, $datasourceUpdateField->validateValue("12"));
        $this->assertSame("Invalid integer value supplied for test", $datasourceUpdateField->validateValue("12.5"));

        // Integers with min and/or max configuration
        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_INTEGER);
        $datasourceUpdateField->setTypeConfig(new NumericFieldTypeConfig(0));
        $this->assertSame(true, $datasourceUpdateField->validateValue(0));
        $this->assertSame(true, $datasourceUpdateField->validateValue(55));
        $this->assertSame("Invalid value supplied for test.  Must be greater than or equal to 0", $datasourceUpdateField->validateValue(-5));




        // Booleans should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_BOOLEAN);
        $this->assertSame(true, $datasourceUpdateField->validateValue(true));
        $this->assertSame("Invalid boolean value supplied for test", $datasourceUpdateField->validateValue("12.5"));


        // Floats should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_FLOAT);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("12"));
        $this->assertEquals(true, $datasourceUpdateField->validateValue("12.5"));
        $this->assertSame("Invalid numeric value supplied for test", $datasourceUpdateField->validateValue("Bad"));

        // Dates should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_DATE);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("1996-01-02"));
        $this->assertSame("Invalid date value supplied for test", $datasourceUpdateField->validateValue("2024-01-01 10:00:00"));

        // Date times should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_DATE_TIME);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("1996-01-02 10:00:00"));
        $this->assertSame("Invalid date time value supplied for test", $datasourceUpdateField->validateValue("2024-01-01"));

        // Required fields should be validated
        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_STRING,false,true);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("Hello"));
        $this->assertSame("Value required for test", $datasourceUpdateField->validateValue(""));
        $this->assertSame("Value required for test", $datasourceUpdateField->validateValue(null));




        // Pick one types should be validated using field config
        $dataset = new ArrayTabularDataset([
            new Field("id"),
            new Field("name")
        ], [
            ["id" => 3, "name" => "Bonzo"],
            ["id" => 5, "name" => "Bingo"],
            ["id" => 7, "name" => "Grumpy"]
        ]);

        $datasetService = MockObjectProvider::mock(DatasetService::class);
        $datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            $dataset, [
                99, [], [], 0, PHP_INT_MAX
            ]);


        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_PICK_FROM_SOURCE, false, false, false, false, [
            "labelFieldName" => "name",
            "valueFieldName" => "id",
            "datasetInstanceId" => 99
        ]);

        $datasourceUpdateField->setDatasetService($datasetService);

        $this->assertTrue($datasourceUpdateField->validateValue(3));
        $this->assertTrue($datasourceUpdateField->validateValue(5));
        $this->assertSame("Invalid value supplied for test", $datasourceUpdateField->validateValue("11"));
        $this->assertSame("Invalid value supplied for test", $datasourceUpdateField->validateValue("Other"));


        // Datasource one
        $datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey",
            $dataset, [
                "example", [], [], 0, PHP_INT_MAX
            ]);


        $datasourceUpdateField = new DatasourceUpdateField("test", "Test", null, Field::TYPE_PICK_FROM_SOURCE, false, false, false, false, [
            "labelFieldName" => "name",
            "valueFieldName" => "id",
            "datasourceInstanceKey" => "example"
        ]);

        $datasourceUpdateField->setDatasourceService($datasourceService);

        $this->assertTrue($datasourceUpdateField->validateValue(3));
        $this->assertTrue($datasourceUpdateField->validateValue(5));
        $this->assertSame("Invalid value supplied for test", $datasourceUpdateField->validateValue("11"));
        $this->assertSame("Invalid value supplied for test", $datasourceUpdateField->validateValue("Other"));





    }




}