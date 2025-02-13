<?php

namespace Kinintel\Test\Objects\FieldValidator;

use Kinintel\Objects\FieldValidator\BooleanFieldValidator;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class BooleanFieldValidatorTest extends TestCase {

    public function testBooleanFieldValidatorCorrectlyValidatesBooleanValuesIncludingZeroAndOne() {

        $validator = new BooleanFieldValidator();
        $field = new DatasourceUpdateField("bingo");

        $this->assertSame(true, $validator->validateValue(true, $field));
        $this->assertSame(true, $validator->validateValue(false, $field));

        $this->assertSame("Invalid boolean value supplied for bingo", $validator->validateValue(1, $field));
        $this->assertSame("Invalid boolean value supplied for bingo", $validator->validateValue(22, $field));
        $this->assertSame("Invalid boolean value supplied for bingo", $validator->validateValue("Hello", $field));
    }

}