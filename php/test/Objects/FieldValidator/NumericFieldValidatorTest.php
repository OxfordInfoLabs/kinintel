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

        $this->assertSame(true, $validator->validateValue(1, $field));
        $this->assertSame(true, $validator->validateValue("1", $field));
        $this->assertSame(true, $validator->validateValue("0.3", $field));
        $this->assertSame(true, $validator->validateValue(0.0043, $field));

        // Blanks and null should be ok.
        $this->assertSame(true, $validator->validateValue("", $field));
        $this->assertSame(true, $validator->validateValue(null, $field));


        $this->assertSame("Invalid numeric value supplied for bingo", $validator->validateValue("Hello", $field));
        $this->assertSame("Invalid numeric value supplied for bingo", $validator->validateValue(true, $field));
    }

    public function testNumericFieldValidatorCorrectlyValidatesNumericValuesWithoutDecimals() {

        $validator = new NumericFieldValidator(false);
        $field = new DatasourceUpdateField("bingo");

        $this->assertSame(true, $validator->validateValue(1, $field));
        $this->assertSame(true, $validator->validateValue("1", $field));

        // Blanks and null should be ok.
        $this->assertSame(true, $validator->validateValue("", $field));
        $this->assertSame(true, $validator->validateValue(null, $field));


        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue("Hello", $field));
        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue(true, $field));
        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue(1.43, $field));
        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue("0.456", $field));



    }

}