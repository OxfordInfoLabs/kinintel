<?php

namespace Kinintel\Test\Objects\Datasource;

use Kinintel\Objects\Datasource\DatasourceDataValidator;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateResultItemValidationErrors;

include_once "autoloader.php";

class DatasourceDataValidatorTest extends \PHPUnit\Framework\TestCase {

    public function testDataReturnedIntactWithNoValidationErrorsForValidatorConfiguredWithRegularFields() {
        $validator = new DatasourceDataValidator([new Field("name"), new Field("age", "Age", null, Field::TYPE_INTEGER)]);

        $data = [
            ["name" => "Bob", "age" => 44],
            ["name" => "Jane", "age" => "Fifty Five"],
            ["name" => "Anne", "age" => 22]
        ];

        $expectedData = $data;

        $validationErrors = $validator->validateUpdateData($data);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([], $validationErrors);

    }

    public function testValidationErrorsReturnedForValidatorConfiguredWithDatasourceUpdateFields() {
        $validator = new DatasourceDataValidator([new DatasourceUpdateField("name"), new DatasourceUpdateField("age", "Age", null, Field::TYPE_INTEGER)]);

        $data = [
            ["name" => "Bob", "age" => 44],
            ["name" => "Jane", "age" => "Fifty Five"],
            ["name" => "Anne", "age" => 22],
            ["name" => "David", "age" => false]
        ];

        $expectedData = $data;

        $validationErrors = $validator->validateUpdateData($data);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(1, ["Invalid integer value supplied for age"]),
            new DatasourceUpdateResultItemValidationErrors(3, ["Invalid integer value supplied for age"])], $validationErrors);
    }


    public function testValidationErrorsReturnedAndDataPrunedForValidatorConfiguredWithDatasourceUpdateFieldsAndPruneInvalidItemsSet() {
        $validator = new DatasourceDataValidator([new DatasourceUpdateField("name"), new DatasourceUpdateField("age", "Age", null, Field::TYPE_INTEGER)]);

        $data = [
            ["name" => "Bob", "age" => 44],
            ["name" => "Jane", "age" => "Fifty Five"],
            ["name" => "Anne", "age" => 22],
            ["name" => "David", "age" => false]
        ];

        $expectedData = $data;
        array_splice($expectedData, 3, 1);
        array_splice($expectedData, 1, 1);

        $validationErrors = $validator->validateUpdateData($data, true);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(1,
            ["Invalid integer value supplied for age"]),
            new DatasourceUpdateResultItemValidationErrors(3,
                ["Invalid integer value supplied for age"])], $validationErrors);
    }

}