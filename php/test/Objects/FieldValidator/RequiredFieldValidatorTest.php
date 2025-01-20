<?php

namespace Kinintel\Test\Objects\FieldValidator;

use Kinintel\Objects\FieldValidator\RequiredFieldValidator;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class RequiredFieldValidatorTest extends TestCase {

    public function testRequiredValidatorReturnsTrueIfNonBlankValueSupplied() {

        $validator = new RequiredFieldValidator();
        $field = new DatasourceUpdateField("bingo");

        $this->assertTrue($validator->validateValue("Hello world", $field));
        $this->assertTrue($validator->validateValue(" ", $field));
        $this->assertTrue($validator->validateValue(0, $field));
        $this->assertTrue($validator->validateValue(false, $field));
    }

    public function testRequiredValidatorReturnsMessageIfBlankValueSupplied() {

        $validator = new RequiredFieldValidator();
        $field = new DatasourceUpdateField("bingo");

        $this->assertEquals("You must supply a non-blank value for bingo", $validator->validateValue(null, $field));
        $this->assertEquals("You must supply a non-blank value for bingo", $validator->validateValue("", $field));


    }
}