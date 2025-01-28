<?php

namespace Kinintel\Test\Objects\FieldValidator;

use Kinintel\Objects\FieldValidator\DateFieldValidator;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DateFieldValidatorTest extends TestCase {

    public function testValidationPerformedCorrectlyForNonTimeBasedDates() {

        $validator = new DateFieldValidator();
        $field = new DatasourceUpdateField("bingo");

        // Valid
        $this->assertTrue($validator->validateValue("2021-01-23", $field));
        $this->assertTrue($validator->validateValue("2024-03-23", $field));

        // Invalid
        $this->assertSame("Invalid date value supplied for bingo", $validator->validateValue("2021-01-23 10:00:00", $field));
        $this->assertSame("Invalid date value supplied for bingo",$validator->validateValue("2021-01-23 09", $field));
        $this->assertSame("Invalid date value supplied for bingo", $validator->validateValue("01/01/2025", $field));
        $this->assertSame("Invalid date value supplied for bingo", $validator->validateValue("Hello", $field));
        $this->assertSame("Invalid date value supplied for bingo", $validator->validateValue(33, $field));
        $this->assertSame("Invalid date value supplied for bingo", $validator->validateValue("2024", $field));

        // Blanks and nulls ok
        $this->assertTrue($validator->validateValue("", $field));
        $this->assertTrue($validator->validateValue(null, $field));


    }


    public function testValidationPerformedCorrectlyForTimeBasedDates() {

        $validator = new DateFieldValidator(true);
        $field = new DatasourceUpdateField("bingo");

        // Valid
        $this->assertTrue($validator->validateValue("2021-01-23 10:00:25", $field));
        $this->assertTrue($validator->validateValue("2024-03-23 09:22:33", $field));
        $this->assertTrue($validator->validateValue("2024-03-23T09:22:33", $field));
        $this->assertTrue($validator->validateValue("2024-03-23T09:22:33", $field));


        // Invalid
        $this->assertSame("Invalid date time value supplied for bingo", $validator->validateValue("2021-01-23", $field));
        $this->assertSame("Invalid date time value supplied for bingo", $validator->validateValue("2021-01-23 09", $field));
        $this->assertSame("Invalid date time value supplied for bingo", $validator->validateValue("Hello", $field));
        $this->assertSame("Invalid date time value supplied for bingo", $validator->validateValue(33, $field));
        $this->assertSame("Invalid date time value supplied for bingo", $validator->validateValue("2024", $field));

        // Blanks and nulls ok
        $this->assertTrue($validator->validateValue("", $field));
        $this->assertTrue($validator->validateValue(null, $field));


    }

}