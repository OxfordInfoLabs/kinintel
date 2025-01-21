<?php

namespace Kinintel\Test\ValueObjects\Datasource\Update;


use Kinintel\ValueObjects\Dataset\Field;
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

    public function testImplicitTypeBasedValidatorsAreUsedToValidateFieldValues(){

        // Integers should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test","Test", null, Field::TYPE_INTEGER);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("12"));
        $this->assertSame("Invalid integer value supplied for test", $datasourceUpdateField->validateValue("12.5"));

        // Floats should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test","Test", null, Field::TYPE_FLOAT);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("12"));
        $this->assertEquals(true, $datasourceUpdateField->validateValue("12.5"));
        $this->assertSame("Invalid numeric value supplied for test", $datasourceUpdateField->validateValue("Bad"));

        // Dates should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test","Test", null, Field::TYPE_DATE);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("1996-01-02"));
        $this->assertSame("Invalid date value supplied for test", $datasourceUpdateField->validateValue("2024-01-01 10:00:00"));

        // Date times should be strictly validated
        $datasourceUpdateField = new DatasourceUpdateField("test","Test", null, Field::TYPE_DATE_TIME);
        $this->assertEquals(true, $datasourceUpdateField->validateValue("1996-01-02 10:00:00"));
        $this->assertSame("Invalid date time value supplied for test", $datasourceUpdateField->validateValue("2024-01-01"));


    }



}