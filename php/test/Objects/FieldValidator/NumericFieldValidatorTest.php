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

        // Null should be ok.
        $this->assertSame(true, $validator->validateValue(null, $field));


        $this->assertSame("Invalid numeric value supplied for bingo", $validator->validateValue("Hello", $field));
        $this->assertSame("Invalid numeric value supplied for bingo", $validator->validateValue("", $field));
        $this->assertSame("Invalid numeric value supplied for bingo", $validator->validateValue(true, $field));
    }

    public function testNumericFieldValidatorCorrectlyValidatesNumericValuesWithoutDecimals() {

        $validator = new NumericFieldValidator(false);
        $field = new DatasourceUpdateField("bingo");

        $this->assertSame(true, $validator->validateValue(1, $field));
        $this->assertSame(true, $validator->validateValue("1", $field));

        // Blanks and null should be ok.
        $this->assertSame(true, $validator->validateValue(null, $field));

        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue("", $field));
        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue("Hello", $field));
        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue(true, $field));
        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue(1.43, $field));
        $this->assertSame("Invalid integer value supplied for bingo", $validator->validateValue("0.456", $field));

    }


    public function testNumericFieldValidatorCorrectlyValidatesNumericValuesWithMinAndMaxValueRules() {

        $validator = new NumericFieldValidator(false, 10);
        $field = new DatasourceUpdateField("bingo");

        $this->assertSame(true, $validator->validateValue(10, $field));
        $this->assertSame(true, $validator->validateValue(50, $field));
        $this->assertSame(true, $validator->validateValue(null, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be greater than or equal to 10", $validator->validateValue(9, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be greater than or equal to 10", $validator->validateValue(0, $field));



        $validator = new NumericFieldValidator(false, null, 10);
        $field = new DatasourceUpdateField("bingo");

        $this->assertSame(true, $validator->validateValue(1, $field));
        $this->assertSame(true, $validator->validateValue(10, $field));
        $this->assertSame(true, $validator->validateValue(null, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be less than or equal to 10", $validator->validateValue(11, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be less than or equal to 10", $validator->validateValue(20, $field));



        $validator = new NumericFieldValidator(false, 5, 10);
        $field = new DatasourceUpdateField("bingo");

        $this->assertSame(true, $validator->validateValue(5, $field));
        $this->assertSame(true, $validator->validateValue(10, $field));
        $this->assertSame(true, $validator->validateValue(null, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be between 5 and 10", $validator->validateValue(1, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be between 5 and 10", $validator->validateValue(20, $field));


        // Edge cases
        $validator = new NumericFieldValidator(false, 0, 0);
        $field = new DatasourceUpdateField("bingo");
        $this->assertSame(true, $validator->validateValue(0, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be between 0 and 0", $validator->validateValue(1, $field));

        $validator = new NumericFieldValidator(false, -20, -10);
        $field = new DatasourceUpdateField("bingo");
        $this->assertSame(true, $validator->validateValue(-15, $field));
        $this->assertSame("Invalid value supplied for bingo.  Must be between -20 and -10", $validator->validateValue(1, $field));


    }


}