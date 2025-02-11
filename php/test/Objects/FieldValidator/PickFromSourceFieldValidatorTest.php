<?php

namespace Kinintel\Test\Objects\FieldValidator;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\FieldValidator\PickFromSourceFieldValidator;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class PickFromSourceFieldValidatorTest extends TestCase {

    public function testCanValidatePickFromValuesForDatasourceConfiguredValidator() {

        $dataset = new ArrayTabularDataset([
            new Field("id"),
            new Field("name")
        ], [
            ["id" => 3, "name" => "Bonzo"],
            ["id" => 5, "name" => "Bingo"],
            ["id" => 7, "name" => "Grumpy"]
        ]);

        $datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey",
            $dataset, [
                "test", [], [], 0, PHP_INT_MAX
            ]);

        $validator = new PickFromSourceFieldValidator("name", null, "test");
        $field = new Field("example");
        $validator->setDatasourceService($datasourceService);

        // Valid values
        $this->assertTrue($validator->validateValue("Bonzo", $field));
        $this->assertTrue($validator->validateValue("Bingo", $field));
        $this->assertTrue($validator->validateValue("Grumpy", $field));

        // Nulls OK
        $this->assertTrue($validator->validateValue(null, $field));


        // Invalid values
        $this->assertSame("Invalid value supplied for example", $validator->validateValue("Happy", $field));
        $this->assertSame("Invalid value supplied for example", $validator->validateValue("11", $field));
        $this->assertSame("Invalid value supplied for example", $validator->validateValue(true, $field));
        $this->assertSame("Invalid value supplied for example", $validator->validateValue("Other", $field));
        $this->assertSame("Invalid value supplied for example", $validator->validateValue("", $field));

    }

    public function testCanValidatePickFromValuesForDatasetConfiguredValidator() {

        $dataset = new ArrayTabularDataset([
            new Field("id"),
            new Field("name")
        ], [
            ["id" => 3, "name" => "Bonzo"],
            ["id" => 5, "name" => "Bingo"],
            ["id" => 7, "name" => "Grumpy"]
        ]);

        $datasetService = MockObjectProvider::mock(DatasetService::class);
        $datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            $dataset, [
                99, [], [], 0, PHP_INT_MAX
            ]);

        $validator = new PickFromSourceFieldValidator("name", 99);
        $field = new Field("example");
        $validator->setDatasetService($datasetService);

        // Valid values
        $this->assertTrue($validator->validateValue("Bonzo", $field));
        $this->assertTrue($validator->validateValue("Bingo", $field));
        $this->assertTrue($validator->validateValue("Grumpy", $field));

        // Blanks and nulls OK
        $this->assertTrue($validator->validateValue(null, $field));


        // Invalid values
        $this->assertSame("Invalid value supplied for example", $validator->validateValue("Happy", $field));
        $this->assertSame("Invalid value supplied for example", $validator->validateValue("11", $field));
        $this->assertSame("Invalid value supplied for example", $validator->validateValue(true, $field));
        $this->assertSame("Invalid value supplied for example", $validator->validateValue("Other", $field));


    }

}