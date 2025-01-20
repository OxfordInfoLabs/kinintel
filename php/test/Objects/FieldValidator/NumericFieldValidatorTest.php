<?php

namespace Kinintel\Test\Objects\FieldValidator;

use Kinintel\Objects\FieldValidator\NumericFieldValidator;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class NumericFieldValidatorTest extends TestCase {


    public function testNumericFieldValidatorCorrectlyValidatesNumericValuesWithDecimalsByDefault() {

        $validator = new NumericFieldValidator();
        $field = new DatasourceUpdateField("bingo");

        $this->assertEquals(true, $validator->validateValue(1, $field));
        $this->assertEquals(true, $validator->validateValue("1", $field));
        $this->assertEquals(true, $validator->validateValue("0.3", $field));
        $this->assertEquals(true, $validator->validateValue(0.0043, $field));

        // Blanks and null should be ok.
        $this->assertEquals(true, $validator->validateValue("", $field));
        $this->assertEquals(true, $validator->validateValue(null, $field));


        $this->assertEquals("You must supply a numeric value for bingo", $validator->validateValue("Hello", $field));
        $this->assertEquals("You must supply a numeric value for bingo", $validator->validateValue(true, $field));
    }

    public function testNumericFieldValidatorCorrectlyValidatesNumericValuesWithoutDecimals() {

        $validator = new NumericFieldValidator(false);
        $field = new DatasourceUpdateField("bingo");

        $this->assertEquals(true, $validator->validateValue(1, $field));
        $this->assertEquals(true, $validator->validateValue("1", $field));

        // Blanks and null should be ok.
        $this->assertEquals(true, $validator->validateValue("", $field));
        $this->assertEquals(true, $validator->validateValue(null, $field));


        $this->assertEquals("You must supply an integer value for bingo", $validator->validateValue("Hello", $field));
        $this->assertEquals("You must supply an integer value for bingo", $validator->validateValue(true, $field));
        $this->assertEquals("You must supply an integer value for bingo", $validator->validateValue(1.43, $field));
        $this->assertEquals("You must supply an integer value for bingo", $validator->validateValue("0.456", $field));



    }

}