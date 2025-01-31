<?php

namespace Kinintel\Test\Services\Hook\Hook;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Hook\Hook\DatasourceUpdateDatasourceHook;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Hook\Hook\DatasourceUpdateDatasourceHookConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasourceUpdateDatasourceHookTest extends TestCase {


    /**
     * @var DatasourceService
     */
    private DatasourceService $datasourceService;

    /**
     * @var DatasourceUpdateDatasourceHook
     */
    private DatasourceUpdateDatasourceHook $hook;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::mock(DatasourceService::class);
        $this->hook = new DatasourceUpdateDatasourceHook($this->datasourceService);
    }

    public function testTargetDatasourceUpdatedWithPassedDataWhenNoFieldsPassed() {

        $config = new DatasourceUpdateDatasourceHookConfig("target");

        $this->hook->processHook($config, DatasourceHookInstance::HOOK_MODE_ADD, [
            [
                "name" => "Mark",
                "age" => 22
            ],
            [
                "name" => "John",
                "age" => 33
            ]
        ]);

        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", [
            "target",
            new DatasourceUpdate([
                [
                    "name" => "Mark",
                    "age" => 22
                ],
                [
                    "name" => "John",
                    "age" => 33
                ]
            ])]));
    }


    public function testTargetDatasourceUpdatedWithDataProcessedViaInterimDatasetIfFieldsSupplied() {

        $config = new DatasourceUpdateDatasourceHookConfig("target", [
            new Field("name"),
            new Field("age"),
            new Field("shoeSize", null, "[[age | subtract 10]]")
        ]);

        $this->hook->processHook($config, DatasourceHookInstance::HOOK_MODE_ADD, [
            [
                "name" => "Mark",
                "age" => 22
            ],
            [
                "name" => "John",
                "age" => 33
            ]
        ]);

        $this->assertTrue($this->datasourceService->methodWasCalled("updateDatasourceInstanceByKey", [
            "target",
            new DatasourceUpdate([
                [
                    "name" => "Mark",
                    "age" => 22,
                    "shoeSize" => 12
                ],
                [
                    "name" => "John",
                    "age" => 33,
                    "shoeSize" => 23
                ]
            ])]));

    }

}