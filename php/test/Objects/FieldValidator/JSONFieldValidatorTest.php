<?php

namespace Kinintel\Test\Objects\FieldValidator;


use Kinintel\Objects\FieldValidator\JSONFieldValidator;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class JSONFieldValidatorTest extends TestCase {

    public function testValidationPerformedCorrectlyForInvalidAndValidJSONValues() {

        $validator = new JSONFieldValidator();
        $field = new DatasourceUpdateField("bingo");

        // Valid JSON values including nulls
        $this->assertTrue($validator->validateValue('"hello"', $field));
        $this->assertTrue($validator->validateValue(3, $field));
        $this->assertTrue($validator->validateValue(null, $field));
        $this->assertTrue($validator->validateValue('3', $field));
        $this->assertTrue($validator->validateValue('{"name": "Mark", "age": 12}', $field));
        $this->assertTrue($validator->validateValue('[{"name": "Mark", "age": 12}, "james", 5]', $field));
        $this->assertTrue($validator->validateValue(["name" => "mark", "age" => 5], $field));

        $this->assertSame("Invalid json value supplied for bingo.  Please ensure that the data is well formed with keys and string values quoted with double quotes.", $validator->validateValue("Mark", $field));
        $this->assertSame("Invalid json value supplied for bingo.  Please ensure that the data is well formed with keys and string values quoted with double quotes.", $validator->validateValue('{Mark', $field));
        $this->assertSame("Invalid json value supplied for bingo.  Please ensure that the data is well formed with keys and string values quoted with double quotes.", $validator->validateValue('{"key": mark}', $field));
        $this->assertSame("Invalid json value supplied for bingo.  Please ensure that the data is well formed with keys and string values quoted with double quotes.", $validator->validateValue('{key: "mark"}', $field));


    }

}