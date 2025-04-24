<?php

namespace Kinintel\Test\Objects\Datasource;

use Kinintel\Objects\Datasource\DatasourceDataValidator;
use Kinintel\Objects\Datasource\UpdatableDatasource;
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

        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_ADD);
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

        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_ADD);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(1, ["age" => "Invalid integer value supplied for age"]),
            new DatasourceUpdateResultItemValidationErrors(3, ["age" => "Invalid integer value supplied for age"])], $validationErrors);
    }


    public function testRequiredValidationErrorReturnedGenerallyForOperationsForRowsWithInsufficientExplicitPrimaryKey(){
        $validator = new DatasourceDataValidator([new DatasourceUpdateField("name",null,null, Field::TYPE_STRING,true), new DatasourceUpdateField("age", "Age", null, Field::TYPE_INTEGER, true)]);

        $data = [
            ["name" => "Bob"],
            ["name" => "Jane", "age" => 44],
            ["name" => "Anne"],
            ["name" => "David", "age" => 25]
        ];

        $expectedData = $data;

        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_ADD);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(0, ["age" => "Value required for age as it is a key field"]),
            new DatasourceUpdateResultItemValidationErrors(2, ["age" => "Value required for age as it is a key field"])], $validationErrors);

    }


    public function testRequiredValidationErrorReturnedForUpdateAndDeleteOperationsForIdPrimaryKey(){
        $validator = new DatasourceDataValidator([new DatasourceUpdateField("identifier",null,null,Field::TYPE_ID), new DatasourceUpdateField("name",null,null, Field::TYPE_STRING,true), new DatasourceUpdateField("age", "Age", null, Field::TYPE_INTEGER, true)]);

        $data = [
            ["name" => "Bob", "age" => 22],
            ["name" => "Jane", "age" => 44],
        ];

        $expectedData = $data;

        // No validation errors expected for adds.
        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_ADD);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([], $validationErrors);

        // Validation errors expected for updates.
        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_UPDATE);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(0, ["identifier" => "Value required for identifier for update of items"]),
            new DatasourceUpdateResultItemValidationErrors(1, ["identifier" => "Value required for identifier for update of items"])], $validationErrors);

        // Validation errors expected for replaces.
        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_REPLACE);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(0, ["identifier" => "Value required for identifier for replace of items"]),
            new DatasourceUpdateResultItemValidationErrors(1, ["identifier" => "Value required for identifier for replace of items"])], $validationErrors);

        // Validation errors expected for deleted.
        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_DELETE);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(0, ["identifier" => "Value required for identifier for delete of items"]),
            new DatasourceUpdateResultItemValidationErrors(1, ["identifier" => "Value required for identifier for delete of items"])], $validationErrors);


        // Conversely, no id should be permitted when adding new items.
        $data = [
            ["identifier" => 5, "name" => "Bob", "age" => 22],
            ["identifier" => 7, "name" => "Jane", "age" => 44],
        ];

        $expectedData = $data;

        // Validation errors expected for updates.
        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_ADD);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(0, ["identifier" => "Value should not be supplied for identifier when adding new items"]),
            new DatasourceUpdateResultItemValidationErrors(1, ["identifier" => "Value should not be supplied for identifier when adding new items"])], $validationErrors);


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

        $validationErrors = $validator->validateUpdateData($data, UpdatableDatasource::UPDATE_MODE_ADD, true);
        $this->assertEquals($expectedData, $data);
        $this->assertEquals([new DatasourceUpdateResultItemValidationErrors(1,
            ["age" => "Invalid integer value supplied for age"]),
            new DatasourceUpdateResultItemValidationErrors(3,
                ["age" => "Invalid integer value supplied for age"])], $validationErrors);
    }



}