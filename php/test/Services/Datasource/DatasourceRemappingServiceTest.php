<?php

namespace Kinintel\Test\Services\Datasource;

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

    //ToDO - rewrite test to use mocked CSV profile

    public function testApplyFieldMapping_canRemapFieldsWithStructuredObject() {


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

        $mapping = [
            "name" => "signal",
            "age" => "abuse_type"
        ];

        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $mapping);

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

    public function testApplyFieldMapping_canRemapFieldsWithNonStructuredObject() {


        $datasourceUpdate = new DatasourceUpdate(
            [
                ["name" => "Joe Bloggs", "age" => 12],
                ["name" => "Mary Jane", "age" => 7]
            ]
        );

        $mapping = [
            "name" => "signal",
            "age" => "abuse_type"
        ];

        $returnedDatasourceUpdate = $this->datasourceRemappingService->applyFieldMapping($datasourceUpdate, $mapping);

        $expectedDatasourceUpdate = new DatasourceUpdate(
            [
                ["signal" => "Joe Bloggs", "abuse_type" => 12],
                ["signal" => "Mary Jane", "abuse_type" => 7]
            ]
        );

        $this->assertEquals($expectedDatasourceUpdate, $returnedDatasourceUpdate);

    }
}
