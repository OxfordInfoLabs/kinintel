<?php

namespace Kinintel\Test\Services\Datasource;

use Kiniauth\Objects\Account\AccountCSVProfile;
use Kiniauth\Objects\Account\AccountCSVProfileSummary;
use Kiniauth\Services\Account\AccountService;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Services\Datasource\DatasourceRemappingService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

include_once "autoloader.php";

class DatasourceRemappingServiceTest extends TestBase {

    /**
     * @var DatasourceRemappingService
     */
    private $datasourceRemappingService;


    /**
     * @return void
     */
    public function setUp(): void {

        $this->datasourceRemappingService = new DatasourceRemappingService(
            Container::instance()->get(AccountService::class)
        );
    }

    public function testApplyFieldMapping_structuredObject_canRemapFields() {

        // set up datasource update
        $datasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER)
            ],
            [],
            [
                ["name" => "Joe Bloggs", "age" => 12],
                ["name" => "Mary Jane", "age" => 7]
            ],
            [
                ["name" => "Mr Smith", "age" => 22],
                ["name" => "Mrs Apple", "age" => 72]
            ],
            [
                ["name" => "Going away", "age" => 33]
            ],
            [
                ["name" => "Replace me", "age" => 88],
                ["name" => "Replace me twice", "age" => 65]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
        );


        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("signal"),
                new Field("abuse_type", null, null, Field::TYPE_INTEGER)
            ],
            [],
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12],
                ["signal" => "Mary Jane", "abuse_type" => 7]
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22],
                ["signal" => "Mrs Apple", "abuse_type" => 72]
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33]
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88],
                ["signal" => "Replace me twice", "abuse_type" => 65]
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    public function testApplyFieldMapping_structuredObject_unmappedFieldsAreIgnored() {

        // set up datasource update
        $datasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER),
                new Field("gender")
            ],
            [],
            [
                ["name" => "Joe Bloggs", "age" => 12, "gender" => "M"],
                ["name" => "Mary Jane", "age" => 7, "gender" => "F"]
            ],
            [
                ["name" => "Mr Smith", "age" => 22, "gender" => "M"],
                ["name" => "Mrs Apple", "age" => 72, "gender" => "F"]
            ],
            [
                ["name" => "Going away", "age" => 33, "gender" => "M"]
            ],
            [
                ["name" => "Replace me", "age" => 88, "gender" => "M"],
                ["name" => "Replace me twice", "age" => 65, "gender" => "F"]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
        );


        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("signal"),
                new Field("abuse_type", null, null, Field::TYPE_INTEGER)
            ],
            [],
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12],
                ["signal" => "Mary Jane", "abuse_type" => 7]
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22],
                ["signal" => "Mrs Apple", "abuse_type" => 72]
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33]
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88],
                ["signal" => "Replace me twice", "abuse_type" => 65]
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }
    public function testApplyFieldMapping_structuredObject_canRemapFieldsWithExtraDataFlags() {

        // set up datasource update
        $datasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER),
                new Field("gender")
            ],
            [],
            [
                ["name" => "Joe Bloggs", "age" => 12, "gender" => "M"],
                ["name" => "Mary Jane", "age" => 7, "gender" => "F"]
            ],
            [
                ["name" => "Mr Smith", "age" => 22, "gender" => "M"],
                ["name" => "Mrs Apple", "age" => 72, "gender" => "F"]
            ],
            [
                ["name" => "Going away", "age" => 33, "gender" => "M"]
            ],
            [
                ["name" => "Replace me", "age" => 88, "gender" => "M"],
                ["name" => "Replace me twice", "age" => 65, "gender" => "F"]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
            [
                "gender" => true
            ]
        );


        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("signal"),
                new Field("abuse_type", null, null, Field::TYPE_INTEGER),
                new Field("extra_data")
            ],
            [],
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12, "extra_data" => json_encode(["gender" => "M"])],
                ["signal" => "Mary Jane", "abuse_type" => 7, "extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22, "extra_data" => json_encode(["gender" => "M"])],
                ["signal" => "Mrs Apple", "abuse_type" => 72, "extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33, "extra_data" => json_encode(["gender" => "M"])],
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88, "extra_data" => json_encode(["gender" => "M"])],
                ["signal" => "Replace me twice", "abuse_type" => 65, "extra_data" => json_encode(["gender" => "F"])],
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    public function testApplyFieldMapping_structuredObject_extraDataNotCreated() {

        // set up datasource update
        $datasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER),
            ],
            [],
            [
                ["name" => "Joe Bloggs", "age" => 12],
                ["name" => "Mary Jane", "age" => 7]
            ],
            [
                ["name" => "Mr Smith", "age" => 22],
                ["name" => "Mrs Apple", "age" => 72]
            ],
            [
                ["name" => "Going away", "age" => 33]
            ],
            [
                ["name" => "Replace me", "age" => 88],
                ["name" => "Replace me twice", "age" => 65]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
            [
                "gender" => true
            ]
        );


        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("signal"),
                new Field("abuse_type", null, null, Field::TYPE_INTEGER),
            ],
            [],
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12],
                ["signal" => "Mary Jane", "abuse_type" => 7],
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22],
                ["signal" => "Mrs Apple", "abuse_type" => 72],
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33],
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88],
                ["signal" => "Replace me twice", "abuse_type" => 65],
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    public function testApplyFieldMapping_structuredObject_emptyMappingWithExtraDataFlags() {

        // set up datasource update
        $datasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("name"),
                new Field("age", null, null, Field::TYPE_INTEGER),
                new Field("gender")
            ],
            [],
            [
                ["name" => "Joe Bloggs", "age" => 12, "gender" => "M"],
                ["name" => "Mary Jane", "age" => 7, "gender" => "F"]
            ],
            [
                ["name" => "Mr Smith", "age" => 22, "gender" => "M"],
                ["name" => "Mrs Apple", "age" => 72, "gender" => "F"]
            ],
            [
                ["name" => "Going away", "age" => 33, "gender" => "M"]
            ],
            [
                ["name" => "Replace me", "age" => 88, "gender" => "M"],
                ["name" => "Replace me twice", "age" => 65, "gender" => "F"]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [],
            [
                "gender" => true
            ]
        );


        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdateWithStructure(
            "Hello world",
            null,
            [
                new Field("extra_data")
            ],
            [],
            [
                ["extra_data" => json_encode(["gender" => "M"])],
                ["extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["extra_data" => json_encode(["gender" => "M"])],
                ["extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["extra_data" => json_encode(["gender" => "M"])],
            ],
            [
                ["extra_data" => json_encode(["gender" => "M"])],
                ["extra_data" => json_encode(["gender" => "F"])],
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }
    public function testApplyFieldMapping_nonStructuredObject_canRemapFields() {


        // set up datasource update
        $datasourceUpdate = new DatasourceUpdate(
            [
                ["name" => "Joe Bloggs", "age" => 12],
                ["name" => "Mary Jane", "age" => 7]
            ],
            [
                ["name" => "Mr Smith", "age" => 22],
                ["name" => "Mrs Apple", "age" => 72]
            ],
            [
                ["name" => "Going away", "age" => 33]
            ],
            [
                ["name" => "Replace me", "age" => 88],
                ["name" => "Replace me twice", "age" => 65]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
        );

        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdate(
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12],
                ["signal" => "Mary Jane", "abuse_type" => 7]
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22],
                ["signal" => "Mrs Apple", "abuse_type" => 72]
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33]
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88],
                ["signal" => "Replace me twice", "abuse_type" => 65]
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    public function testApplyFieldMapping_nonStructuredObject_unmappedFieldsAreIgnored() {


        // set up datasource update
        $datasourceUpdate = new DatasourceUpdate(
            [
                ["name" => "Joe Bloggs", "age" => 12, "gender" => "M"],
                ["name" => "Mary Jane", "age" => 7, "gender" => "F"]
            ],
            [
                ["name" => "Mr Smith", "age" => 22, "gender" => "M"],
                ["name" => "Mrs Apple", "age" => 72, "gender" => "F"]
            ],
            [
                ["name" => "Going away", "age" => 33, "gender" => "M"]
            ],
            [
                ["name" => "Replace me", "age" => 88, "gender" => "M"],
                ["name" => "Replace me twice", "age" => 65, "gender" => "F"]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
        );

        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdate(
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12],
                ["signal" => "Mary Jane", "abuse_type" => 7]
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22],
                ["signal" => "Mrs Apple", "abuse_type" => 72]
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33]
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88],
                ["signal" => "Replace me twice", "abuse_type" => 65]
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    public function testApplyFieldMapping_nonStructuredObject_canRemapFieldsWithExtraDataFlags() {


        // set up datasource update
        $datasourceUpdate = new DatasourceUpdate(
            [
                ["name" => "Joe Bloggs", "age" => 12, "gender" => "M"],
                ["name" => "Mary Jane", "age" => 7, "gender" => "F"]
            ],
            [
                ["name" => "Mr Smith", "age" => 22, "gender" => "M"],
                ["name" => "Mrs Apple", "age" => 72, "gender" => "F"]
            ],
            [
                ["name" => "Going away", "age" => 33, "gender" => "M"]
            ],
            [
                ["name" => "Replace me", "age" => 88, "gender" => "M"],
                ["name" => "Replace me twice", "age" => 65, "gender" => "F"]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
            [
                "gender" => true
            ]
        );

        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdate(
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12, "extra_data" => json_encode(["gender" => "M"])],
                ["signal" => "Mary Jane", "abuse_type" => 7, "extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22, "extra_data" => json_encode(["gender" => "M"])],
                ["signal" => "Mrs Apple", "abuse_type" => 72, "extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33, "extra_data" => json_encode(["gender" => "M"])],
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88, "extra_data" => json_encode(["gender" => "M"])],
                ["signal" => "Replace me twice", "abuse_type" => 65, "extra_data" => json_encode(["gender" => "F"])],
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    public function testApplyFieldMapping_nonStructuredObject_extraDataNotCreated() {


        // set up datasource update
        $datasourceUpdate = new DatasourceUpdate(
            [
                ["name" => "Joe Bloggs", "age" => 12],
                ["name" => "Mary Jane", "age" => 7]
            ],
            [
                ["name" => "Mr Smith", "age" => 22],
                ["name" => "Mrs Apple", "age" => 72]
            ],
            [
                ["name" => "Going away", "age" => 33]
            ],
            [
                ["name" => "Replace me", "age" => 88],
                ["name" => "Replace me twice", "age" => 65]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [
                "name" => "signal",
                "age" => "abuse_type"
            ],
            [
                "gender" => true
            ]
        );

        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdate(
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12],
                ["signal" => "Mary Jane", "abuse_type" => 7],
            ],
            [
                ["signal" => "Mr Smith", "abuse_type" => 22],
                ["signal" => "Mrs Apple", "abuse_type" => 72],
            ],
            [
                ["signal" => "Going away", "abuse_type" => 33],
            ],
            [
                ["signal" => "Replace me", "abuse_type" => 88],
                ["signal" => "Replace me twice", "abuse_type" => 65],
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    public function testApplyFieldMapping_nonStructuredObject_emptyMappingWithExtraDataFlags() {


        // set up datasource update
        $datasourceUpdate = new DatasourceUpdate(
            [
                ["name" => "Joe Bloggs", "age" => 12, "gender" => "M"],
                ["name" => "Mary Jane", "age" => 7, "gender" => "F"]
            ],
            [
                ["name" => "Mr Smith", "age" => 22, "gender" => "M"],
                ["name" => "Mrs Apple", "age" => 72, "gender" => "F"]
            ],
            [
                ["name" => "Going away", "age" => 33, "gender" => "M"]
            ],
            [
                ["name" => "Replace me", "age" => 88, "gender" => "M"],
                ["name" => "Replace me twice", "age" => 65, "gender" => "F"]
            ]
        );

        // set up CSV profile
        $csvProfile = $this->createMockCSVProfile(
            [],
            [
                "gender" => true
            ]
        );

        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $csvProfile);

        $expectedDatasourceUpdate = new DatasourceUpdate(
            [
                ["extra_data" => json_encode(["gender" => "M"])],
                ["extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["extra_data" => json_encode(["gender" => "M"])],
                ["extra_data" => json_encode(["gender" => "F"])],
            ],
            [
                ["extra_data" => json_encode(["gender" => "M"])],
            ],
            [
                ["extra_data" => json_encode(["gender" => "M"])],
                ["extra_data" => json_encode(["gender" => "F"])],
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }

    /**
     * helper function to create a new mocked CSV profile
     *
     * @param array $mapping
     * @param array $extraDataFlags
     *
     * @return AccountCSVProfile
     */
    public function createMockCSVProfile($mapping, $extraDataFlags = []) {

        $summary = $this->createMock(AccountCSVProfileSummary::class);

        $summary->method("getMapping")
            ->willReturn($mapping);

        $summary->method("getExtraDataFlags")
            ->willReturn($extraDataFlags);

        $csvProfile = $this->createMock(AccountCSVProfile::class);

        $csvProfile
            ->method("returnSummary")
            ->willReturn($summary);

        return $csvProfile;
    }
}
